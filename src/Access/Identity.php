<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2018-01-11
 * Time: 12:38 PM
 */

namespace W3\Access;


class Identity extends \ArrayObject
{
    /**
     * Identity constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data, self::ARRAY_AS_PROPS);
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRole() : string
    {
        return $this->role;
    }

    /**
     * @return array
     */
    public function getRoles() : array
    {
        return [$this->role];
    }

    /**
     * @return bool
     */
    public function isAdmin() : bool
    {
        return $this->getRole() === AuthService::ROLE_ADMIN;
    }

    /**
     * @param array $arr
     * @return Identity
     */
    public static function fromArray(array $arr) : Identity
    {
        return new self($arr);
    }
}