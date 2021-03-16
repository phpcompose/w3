<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2018-01-10
 * Time: 11:12 AM
 */

namespace W3\Access;


use Compose\Container\ResolvableInterface;
use Compose\Support\Configuration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class AccessMiddleware implements MiddlewareInterface, ResolvableInterface
{
    protected
        $config,

        /**
         * @var AuthService
         */
        $auth;

    /**
     * AccessMiddleware constructor.
     * @param AuthService $auth
     */
    public function __construct(AuthService $auth, Configuration $configuration)
    {
        $this->auth = $auth;
        $this->config = $configuration;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     * @throws NotAuthorizedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $identity = null;
        if($this->auth->hasIdentity()) {
            $identity = $this->auth->getIdentity();
            // store identity in the request attribute
            $request = $request->withAttribute(Identity::class, $identity);
        }

        if(!$this->auth->authorize($request)) {
            $redirect = $this->config['access']['auth_url'] ?? null;
            $redirect_param = $this->config['access']['auth_url_param'] ?? 'resource';
            if(!$identity && $redirect) { // only redirect if no user is logged in, and redirect is provided
                return new RedirectResponse($redirect . "?{$redirect_param}=" . $request->getUri()->getPath());
            }

            // no redirect found, or user is logged in with wrong role
            throw new NotAuthorizedException("{$identity->getId()} is not authorized to access requested resource.");
        }

        return $handler->handle($request);
    }
}