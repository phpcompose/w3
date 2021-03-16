<?php


namespace W3\Support\Util;


use Compose\Http\Session;

class CsrfToken
{
    /**
     * CsrfToken constructor.
     * @param Session $session
     * @param string $tokenKey
     */
    public function __construct(Session $session, string $tokenKey = '__csrf_token')
    {
        $this->session = $session;
        $this->tokenKey = $tokenKey;
        $this->tokenKey2 = $tokenKey . '2';
    }

    /**
     * @param null $resource
     * @return string
     * @throws \Exception
     */
    public function getToken($resource = null) : string
    {
        if(!$this->session->has($this->tokenKey)) {
            $this->session->set($this->tokenKey, bin2hex(random_bytes(32)));
        }

        $token = $this->session->get($this->tokenKey);

        if($resource) {
            if(!$this->session->has($this->tokenKey2)) {
                $this->session->set($this->tokenKey2, random_bytes(32));
            }

            $token2 = $this->session->get($this->tokenKey2);
            return hash_hmac('md5', $resource, $token2);
        }

        return $token;
    }

    /**
     * @param string $token
     * @param string|null $resource
     * @return bool
     */
    public function checkToken(string $token, string $resource = null) : bool
    {
        if($resource) {
            $val = hash_hmac('md5', $resource, $this->session->get($this->tokenKey2));
            return hash_equals($val, $token);
        }

        return hash_equals($this->session->get($this->tokenKey), $token);
    }

    /**
     *
     */
    public function clear() : void
    {
        $this->session->unset($this->tokenKey);
        $this->session->unset($this->tokenKey2);
    }
}