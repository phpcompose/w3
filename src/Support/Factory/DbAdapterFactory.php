<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2018-04-07
 * Time: 4:10 PM
 */

namespace W3\Support\Factory;


use Compose\Container\ServiceFactoryInterface;
use Compose\Support\Configuration;
use Psr\Container\ContainerInterface;
use Zend\Db\Adapter\Adapter;

class DbAdapterFactory implements ServiceFactoryInterface
{
    public static function create(ContainerInterface $container, string $name)
    {
        $config = $container->get(Configuration::class);
        $dbconfig = $config['database'] ?? null;
        $adater = new Adapter($dbconfig);

        return $adater;
    }
}