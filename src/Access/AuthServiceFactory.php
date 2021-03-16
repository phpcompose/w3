<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2019-03-24
 * Time: 13:25
 */

namespace W3\Access;


use Compose\Container\ServiceFactoryInterface;
use Compose\Http\Session;
use Compose\Support\Configuration;
use Psr\Container\ContainerInterface;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Permissions\Rbac\Rbac;

class AuthServiceFactory implements ServiceFactoryInterface
{
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
        $config = $container->get(Configuration::class);
        $rbac = new Rbac();
        $rbac->setCreateMissingRoles(true);
        $session = $container->get(Session::class);

        $adapter = null;
        if($container->has(AdapterInterface::class)) {
            $adapter = $container->get(AdapterInterface::class);
        }

        $session_namespace = $config['access']['session_namespace'] ?? null;
        $auth = new AuthenticationService(
            new AuthSessionStorage($session, $session_namespace),
            $adapter);

        $instance = new AuthService($auth, $rbac, $container->get(JwtService::class));


        $roles = $config['access']['roles'] ?? null;
        $permissions = $config['access']['permissions'];
        $instance->addRoles($roles);
        $instance->addPermissions($permissions);

        return $instance;
    }
}