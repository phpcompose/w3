<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2019-03-21
 * Time: 13:56
 */

namespace W3\Access;

use Psr\Http\Message\RequestInterface;
use Zend\Authentication\Result;

/**
 * Class JwtAdapter
 * @package W3\Access
 */
class JwtRequestAdapter
{
    /**
     * map identity key in the jwt provided by the auth service/*
     */
    const JWT_IDENTITY_KEY = 'identity';

    protected
        /**
         * @var JwtService
         */
        $jwtService,

        /**
         * @var string
         */
        $token,

        /**
         * @var RequestInterface
         */
        $request;

    /**
     * JwtAdapter constructor.
     * @param RequestInterface $request
     * @param JwtService $jwtService
     */
    public function __construct(RequestInterface $request, JwtService $jwtService)
    {
        $this->request = $request;
        $this->jwtService = $jwtService;
    }

    public function getToken() : string
    {
        return $this->token;
    }

    /**
     * @inheritdoc
     */
    public function authenticate() : Result
    {
        $request = $this->request;

        // check for header authentication
        $header = current($request->getHeader('authorization'));
        if(!$header) {
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
        }

        // check for token
        list(, $token) = explode(' ', $header);
        if(!$token) {
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
        }

        // attempt to read identity from jwt
        try {
            $jwt = $this->jwtService->decode($token);
            $identity = $jwt[self::JWT_IDENTITY_KEY];
            return new Result(Result::SUCCESS, $identity);
        } catch (JwtException $e) {
            return new Result(Result::FAILURE, null, [$e->getMessage()]);
        }
    }
}