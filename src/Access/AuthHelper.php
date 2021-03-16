<?php


namespace W3\Access;


use Compose\Mvc\Helper\HelperRegistry;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AuthHelper
 * @package W3\Access
 */
class AuthHelper
{
    /**
     * @var HelperRegistry
     */
    public $registry;


    protected
        /**
         * @var AuthService
         */
        $authService,

        /**
         * @var Identity
         */
        $identity;

    /**
     * AuthHelper constructor.
     * @param AuthService $authService
     * @param ServerRequestInterface $request
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @return Identity|null
     */
    public function identity() : ?Identity
    {
        if(!$this->registry->currentRequest) {
            return null;
        }
        
        return $this->registry->currentRequest->getAttribute(Identity::class);
    }
}