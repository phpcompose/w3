<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2019-03-21
 * Time: 14:01
 */

namespace W3\Access;


use Exception;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * Class JwtException
 * @package W3\Access
 */
class JwtException extends \Exception
{
    const
        ERR_BEFORE_VALID = 1,
        ERR_EXPIRED = 2,
        ERR_SIGNATURE = 3,

        ERR_ALGORITHM = 9,

        ERR_INVALID_ENCODING = 12,
        ERR_TOKEN = 13;

    /**
     * JwtException constructor.
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = null, int $code = null, Exception $previous = null)
    {
        $code = $this->getCodeForException($previous);
        parent::__construct($previous->getMessage(), $code, $previous);
    }

    /**
     * @param Exception $e
     * @return int
     */
    protected function getCodeForException(Exception $e) : int
    {
        if($e instanceof ExpiredException) {
            return self::ERR_EXPIRED;
        } else if($e instanceof BeforeValidException) {
            return self::ERR_BEFORE_VALID;
        } else if($e instanceof SignatureInvalidException) {
            return self::ERR_SIGNATURE;
        } else  {
            return self::ERR_TOKEN;
        }
    }
}