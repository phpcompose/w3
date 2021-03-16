<?php


namespace W3\Log;


use Monolog\Processor\ProcessorInterface;
use W3\Access\AuthService;

/**
 * Add current user identity information
 *
 * Class IdentityProcessor
 * @package W3\Log
 */
class IdentityProcessor implements ProcessorInterface
{
    /**
     * @var AuthService
     */
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


    /**
     * @param array $records
     * @return array
     */
    public function __invoke(array $records)
    {
        $identity = $this->authService->getIdentity();
        if(!$identity) {
            return $records;
        }

        $records['extra']['identity_id'] = $identity->getId();
        $records['extra']['identity_role'] = implode(', ', $identity->getRoles());
        $records['extra']['identity'] = json_encode($identity);

        return $records;
    }
}