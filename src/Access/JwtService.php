<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2019-03-21
 * Time: 13:56
 */

namespace W3\Access;

use Compose\Container\ResolvableInterface;
use Compose\Container\ServiceFactoryInterface;
use Compose\Support\Configuration;
use Firebase\JWT\JWT;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class JwtService
 * @package W3\Access
 */
class JwtService implements ServiceFactoryInterface
{
    protected
        /**
         * Secrete key
         * @var
         */
        $key,

        /**
         * @var int
         */
        $exp,

        /**
         * @var int
         */
        $timeOffset = 60,

        /**
         * @var array
         */
        $supportedAlgorithms = ['HS256'];

    /**
     * JwtService constructor.
     * @todo update to support private/public key or shared key
     * @todo provide additional configuration (algorithm/types, etc)
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->key = $config['key'] ?? null;
        $this->exp = $config['ttl'] ?? 0;

        // setup the jwt library
        JWT::$leeway = $this->timeOffset;
        JWT::$timestamp = time();
    }

    /**
     * Create/initialize the Service
     *
     * @todo Move to separate Factory Class
     * @param ContainerInterface $container
     * @param string $name
     * @return mixed|JwtService
     */
    public static function create(ContainerInterface $container, string $name)
    {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        return new self([
            'key' => $config->getNestedValue('access.jwt_key', null, '.'),
            'ttl' => $config->getNestedValue('access.jwt_ttl', null, '.')
        ]);
    }

    /**
     * Create a JWT payload from arbitrary array with claims
     * @param Identity $identity
     * @return array
     */
    public function createPayloadFromArray(array $claims): array
    {
        $claims['iat'] = time();
        $claims['nbf'] = $claims['nbf'] ?? $claims['iat'];
        $claims['exp'] = $claims['exp'] ?? time() + $this->exp;
        $claims['zoneinfo'] = date_default_timezone_get();

        return $claims;
    }

    /**
     * Encode JWT into token string
     * @param array $jwt
     * @return string
     */
    public function encode(array $jwt): string
    {
        return JWT::encode($jwt, $this->key);
    }

    /**
     * Attempt to decode JWT token
     *
     * ALWAYS wrap this call with try/catch block.
     * Use the JwtException (see doc) to detect error type, if any
     * @param string $token
     * @return array
     * @throws JwtException
     */
    public function decode(string $token): array
    {
        try {
            $obj = JWT::decode($token, $this->key, $this->supportedAlgorithms);
        } catch(\Exception $e) {
            throw new JwtException("Invalid Token", 0, $e);
        }

        return (array) $obj;
    }

    /**
     * @param ServerRequestInterface $request
     * @return JwtRequestAuthAdapter
     */
    public function createAuthAdapter(ServerRequestInterface $request) : JwtRequestAuthAdapter
    {
        return new JwtRequestAuthAdapter($request, $this);
    }
}