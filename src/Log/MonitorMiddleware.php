<?php


namespace W3\Log;


use App\Auth\AccessManager;
use Compose\Container\ResolvableInterface;
use Compose\Http\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use W3\Access\AuthService;

/**
 * Class MonitorMiddleware
 * @package App\Middleware
 */
class MonitorMiddleware implements MiddlewareInterface, ResolvableInterface
{
    protected
        /** @var AccessManager */
        $accessManager,

        /** @var Session */
        $session,

        /** @var AuthService */
        $authService;

    /**
     * MonitorMiddleware constructor.
     * @param AccessManager $accessManager
     * @param Session $session
     * @param AuthService $authService
     */
    public function __construct(AccessManager $accessManager, Session $session, AuthService $authService)
    {
        $this->accessManager = $accessManager;
        $this->session = $session;
        $this->authService = $authService;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {


        return $handler->handle($request);
    }
}