<?php

namespace W3\Access;


use Compose\Container\ServiceFactoryInterface;
use Compose\Http\Session;
use Compose\Support\Configuration;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Permissions\Rbac\Rbac;

class AuthService implements ServiceFactoryInterface
{
    protected
        $guards = [],

        /**
         * @var AuthenticationService
         */
        $auth,

        /**
         * @var Rbac
         */
        $rbac;

    const
        ROLE_GUEST = 'guest',
        ROLE_ADMIN = 'admin',
        ROLE_USER = 'user',
        ERR_NO_IDENTITY = -1,
        ERR_NOT_AUTHORIZE = -2;

    /**
     * AuthService constructor.
     * @param AuthenticationService $auth
     * @param Rbac $rbac
     */
    public function __construct(AuthenticationService $auth, Rbac $rbac)
    {
        $this->auth = $auth;
        $this->rbac = $rbac;
    }

    /**
     * Store factory method for instantiating and configuring the Rbac
     *
     * @param ContainerInterface $container
     * @return object|AuthService
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    static public function create(ContainerInterface $container, string $name)
    {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);

        // create the authentication
        $adapter = $config['access']['adapter'] ?? null;
        $storage = $config['access']['storage'] ?? null;
        $auth = new AuthenticationService(
            $storage ? $container->get($storage) : null,
            $adapter ? $container->get($adapter) : null
        );

        // create authorization
        $rbac = new Rbac();
        $rbac->setCreateMissingRoles(true);

        // create the service
        $instance = new self($auth, $rbac, $config);

        // setup
        $roles = $config['access']['roles'] ?? null;
        $protected = $config['access']['guards'];
        if($roles) $instance->addRoles($roles);
        if($protected) $instance->addGuards($protected);

        return $instance;
    }

    /**
     * @param array $guards
     */
    public function addGuards(array $guards)
    {
        foreach($guards as $path => $roles) {
            if(!isset($this->guards[$path])) {
                $this->guards[$path] = [];
            }

            if(!is_array($roles)) {
                $roles = [$roles];
            }

            array_push($this->guards[$path], ...$roles);

            foreach($roles as $role) {
                $aRole = $this->rbac->getRole($role);
                $aRole->addPermission($path);
            }
        }
    }

    /**
     * @param array $roles
     * @return AuthService
     */
    public function addRoles(array $roles) : self
    {
        $rbac = $this->rbac;
        foreach ($roles as $role => $children) {
            if(!$rbac->hasRole($role)) {
                $rbac->addRole($role);
            }
            if(!$children) continue;

            $aRole = $rbac->getRole($role);
            $children = (array) $children;
            foreach($children as $child) {
                if(!$rbac->hasRole($child)) $rbac->addRole($child);
                $aChild = $rbac->getRole($child);
                $aRole->addChild($aChild);
            }
        }

        return $this;
    }

    /**
     * @param array $rules
     * @return AuthService
     */
    public function addPermissions(array $rules) : self
    {
        $rbac = $this->rbac;
        foreach($rules as $role => $permissions) {
            foreach($permissions as $permission) {
                $rbac->getRole($role)->addPermission($permission);
            }
        }

        return $this;
    }

    public function setStorage(StorageInterface $storage)
    {

    }

    /**
     * @return null|Identity
     */
    public function getIdentity() : ?Identity
    {
        return $this->auth->getIdentity();
    }

    /**
     * @return bool
     */
    public function hasIdentity() : bool
    {
        return $this->auth->hasIdentity();
    }

    /**
     *
     */
    public function clearIdentity()
    {
        $this->auth->clearIdentity();
    }

    /**
     * @return Identity
     * @throws InvalidCredentialException
     */
    public function authenticate(AdapterInterface $adapter) : Identity
    {
        $result = $this->auth->authenticate($adapter);
        if(!$result->isValid()) {
            throw new InvalidCredentialException(
                implode(';', $result->getMessages()), $result->getCode());
        }

        return $this->getIdentity();
    }


    /**
     * Checks permission for given $role
     *
     * @note admin role ALWAYS permitted to everything.
     * @param string $role
     * @param array $permissions
     * @return bool
     */
    public function hasPermissions(string $role, array $permissions) : bool
    {
        $rbac = $this->rbac;

        // admin has access to all permissions
        if($role == self::ROLE_ADMIN) return true;

        // for all other roles, check permissions
        foreach($permissions as $permission) {
            if(!$rbac->isGranted($role, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $roles
     * @param array $permissions
     * @return bool
     */
    public function hasPermissionsForRoles(array  $roles, array $permissions) : bool
    {
        foreach($roles as $role) {
            if($this->hasPermissions($role, $permissions)) return true;
        }

        return false;
    }

    /**
     * @return Identity
     */
    public function createGuestIdentity() : Identity
    {
        return new Identity([
           'id' => 'guest',
           'role' => self::ROLE_GUEST
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function authorize(ServerRequestInterface $request) : bool
    {
        $identity = $request->getAttribute(Identity::class);
        if(!$identity) {
            $identity = $this->createGuestIdentity();
        }

        $roles = $identity->getRoles();
        $resource= $this->getResourceForRequest($request);
        if(!$resource) { // this request is not guarded, then do nothing
            return true;
        }

        return $this->hasPermissionsForRoles($roles, [$resource]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return array|null
     */
    protected function getResourceForRequest(ServerRequestInterface $request) : ?string
    {
        $uri = $request->getUri();
        $normalize = function($path) {
            return trim($path, '/') . '/';
        };

        $resources = array_keys($this->guards);
        rsort($resources);
        $path = $normalize($uri->getPath());

        $requestedResource = null;
        $requiredRoles = null;
        foreach($resources as $resource) {
            if(strpos($path, $normalize($resource), 0) === 0) {
                return $resource;
            }
        }

        return null;
    }

    protected function getPathAndMethods($path) {
        // extract url and http method from the path
        preg_match('#\((.*?)\)#', $path, $match);
        if(isset($match[1])) { // HTTP method is provided
            $methods = explode(',', str_replace(' ', '', $match[1]));
            $url = trim(str_replace($match[0], '', $path));
        } else {
            $url = trim($path);
            $methods = null;
        }

        return [$url, $methods];
    }
}