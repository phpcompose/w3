<?php
namespace W3;


use Psr\Log\LoggerInterface;
use W3\Access\AccessMiddleware;
use W3\Access\AuthHelper;
use W3\Access\AuthService;
use W3\Access\AuthSessionStorage;
use W3\Log\MonologFactory;
use W3\Module\Contact\ContactApiController;
use W3\Support\Factory\DbAdapterFactory;
use W3\Log\LogErrorListener;
use Laminas\Db\Adapter\AdapterInterface;

class Config
{
    public function __invoke()
    {
        return [
            /**
             * Specify log handlers for monolog service
             */
            'log_handlers' => [

            ],

            'error_listeners' => [
                LogErrorListener::class // register error listener for logging errors (except 404)
            ],

            'services' => [
                LogErrorListener::class,
                AdapterInterface::class => DbAdapterFactory::class,
                LoggerInterface::class => MonologFactory::class
            ],

            'middleware' => [
                AccessMiddleware::class
            ],

            'routes' => [
//                'contact/api' => ContactApiController::class
            ],

            'logs' => [

            ],

            'helpers' => [
                AuthHelper::class
            ],

            'emailer' => [
                'plugin' => null,
                'options' => []
            ],

            /**
             * access
             *
             * Manage authentication and authorization
             */
            'access' => [
                // authentication adapter
                'adapter' => null,
                'storage' => AuthSessionStorage::class,
                'auth_url' => '/auth/login', // if not provided, unauthorized exception will be raised
                'auth_url_param' => 'resource',

                // authorization roles
                'roles' => [
                    AuthService::ROLE_GUEST => null,
                    AuthService::ROLE_USER => [AuthService::ROLE_GUEST], // inherits from guest
                    AuthService::ROLE_ADMIN => null,
                ],

                // resource/folders protected by specific roles
                // every request will be authorized for specified resources
                'guards' => [
                ],

                // specific permission given to each roles
                // controllers may check for specific permission before performing
                'permissions' => [
                ]
            ]
        ];
    }
}
