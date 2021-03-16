<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2019-03-27
 * Time: 15:43
 */

namespace W3\Log;


use Compose\Container\ServiceFactoryInterface;
use Compose\Support\Configuration;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Container\ContainerInterface;
use W3\Access\AuthService;

/**
 * Class MonologFactory
 * @package W3\Log
 */
class MonologFactory implements  ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @return mixed|Logger
     * @throws \Exception
     */
    public static function create(ContainerInterface $container, string $name)
    {
        $config = $container->get(Configuration::class);
        $logger = new Logger('app');

        // first push the error handler
        $logger->pushHandler(new ErrorLogHandler(0, Logger::ERROR));

        $handlers = $config['log_handlers'] ?? [];
        foreach ($handlers as $handler) {
            $logger->pushHandler($container->get($handler));
        }

//        $logger->pushProcessor(new IntrospectionProcessor());   // trace
        $logger->pushProcessor(new WebProcessor($_SERVER));     // web  context
        $logger->pushProcessor(new IdentityProcessor($container->get(AuthService::class)));
        return $logger;
    }
}