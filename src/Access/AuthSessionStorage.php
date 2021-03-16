<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2018-01-10
 * Time: 2:57 PM
 */

namespace W3\Access;


use Compose\Container\ResolvableInterface;
use Compose\Http\Session;
use Zend\Authentication\Storage\StorageInterface;

/**
 * Class AuthSessionStorage
 *
 * Session based authentication session.
 * @package W3\Access
 */
class AuthSessionStorage implements StorageInterface, ResolvableInterface
{
    protected
        /**
         * @var string
         */
        $key = 'w3.session.auth',

        /**
         * @var Session
         */
        $session;


    /**
     * AuthStorage constructor.
     * @param Session $session
     * @param string $namespace
     */
    public function __construct(Session $session, string $namespace = null)
    {
        $this->session = $session;
        if($namespace) {
            $this->key = $namespace;
        }
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->session->has($this->key);
    }

    /**
     * @inheritdoc
     * @return mixed|null
     */
    public function read()
    {
        return unserialize($this->session->get($this->key));
    }

    /**
     * @inheritdoc
     * @param mixed $contents
     */
    public function write($contents)
    {
        $this->session->set($this->key,
            is_string($contents) ? $contents : serialize($contents)
            );
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->session->unset($this->key);
    }
}