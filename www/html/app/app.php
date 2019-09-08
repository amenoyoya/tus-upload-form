<?php

namespace Slim\Framework;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * slim framework app
 */
class Application {
    private static $app;

    public static function create()
    {
        self::$app = new \Slim\App([
            'settings' => [
                'displayErrorDetails' => true,
            ],
            'notFoundHandler' => function ($c) {
                return function (Request $request, Response $response) {
                    // 存在しないページを指定されたときは常に home リダイレクト
                    return $response->withStatus(302)->withHeader('Location', '/?redirect=' . $request->getUri()->getPath());
                };
            },
        ]);
    }

    public static function get($route, callable $callback)
    {
        return self::$app->get($route, $callback);
    }

    public static function post($route, callable $callback)
    {
        return self::$app->post($route, $callback);
    }

    public static function put($route, callable $callback)
    {
        return self::$app->put($route, $callback);
    }

    public static function delete($route, callable $callback)
    {
        return self::$app->delete($route, $callback);
    }

    public static function api($method, $route, callable $callback)
    {
        return self::$app->post($route, function (Request $request, Response $response, array $args) use ($callback) {
            // only accept json data
            $json = json_decode($request->getBody(), true);
            // confirm csrf token & host name
            if (!isset($_SESSION['csrf_token']) ||
                !isset($json['csrf']) ||
                $_SESSION['csrf_token'] !== $json['csrf'] ||
                $request->getUri()->getHost() !== CONFIG['web']['host_name']
            ) {
                return $response->withStatus(403); // Forbidden error
            }
            // callback: return array $json;
            $response->getBody()->write(json_encode($callback($request, $response, $args, $json)));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }

    public static function execute()
    {
        return self::$app->run();
    }
}
