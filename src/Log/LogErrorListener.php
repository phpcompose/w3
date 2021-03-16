<?php
/**
 * Created by PhpStorm.
 * User: alaminahmed
 * Date: 2019-03-15
 * Time: 11:07
 */

namespace W3\Log;

use Compose\Http\HttpException;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use W3\Access\Identity;

/**
 * Logs all errors, except for 404/page not found
 *
 * Class LogErrorListener
 * @package W3\Log
 */
class LogErrorListener
{
    /**
     * Log message string with placeholders
     */
    const LOG_STRING = '{status} [{method}] {uri}: {error}';

    private $logger;

    /**
     * LogErrorListener constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \Throwable $error
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __invoke(\Throwable $error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $code = $error->getCode();

        if($code === 404 || $code === 400) { // ignore 404/404 (invalid/incomplete request) errors
            return;
        }

        if($code >= 400 && $code < 500) {
            $level = Logger::WARNING;
        } else {
            $level = Logger::ERROR;
        }

        $this->logger->log($level, "[{$error->getCode()}] {$error->getMessage()} [{$error->getFile()} :: {$error->getLine()}]", [
            'exception_type' => get_class($error),
            'exception_code' => $error->getCode(),
            'exception_message' => $error->getMessage(),
            'exception_file' => $error->getFile(),
            'exception_line' => $error->getLine(),
            'http_code' => http_response_code()
        ]);
    }
}