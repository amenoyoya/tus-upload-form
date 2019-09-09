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

    public static function options($route, callable $callback)
    {
        return self::$app->options($route, $callback);
    }

    public static function patch($route, callable $callback)
    {
        return self::$app->patch($route, $callback);
    }

    public static function map(array $methods, $route, callable $callback)
    {
        return self::$app->map($methods, $route, $callback);
    }

    public static function execute()
    {
        return self::$app->run();
    }

    /**
     * クライアントIPアドレス取得
     */
    public static function getClientIp()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * CORS対応Response生成
     */
    public static function CORS(Response $response)
    {
        // 全てのリクエスト元, リクエストメソッド, リクエストヘッダー, レスポンスヘッダーを許可
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Expose-Headers', '*');
    }

    /**
     * 外部実行用API定義メソッド: 誰でも or 許可されたIP から実行可能
     */
    public static function api($method, $route, callable $callback)
    {
        $proc = function (Request $request, Response $response, array $args) use ($callback) {
            // only accept json data
            $json = json_decode($request->getBody(), true);
            // check ips
            if (!self::checkIPs()) {
                return self::CORS($response->withStatus(403)); // Forbidden error
            }
            // callback
            $res = $callback($request, $response, $args, $json? $json: []);
            if (is_array($res)) {
                // return json data
                $response->getBody()->write(json_encode($res));
                return self::CORS($response->withHeader('Content-Type', 'application/json'));
            }
            return self::CORS($res);
        };
        if (is_array($method)) {
            return self::$app->map($method, $route, $proc);
        }
        return self::$app->$method($route, $proc);
    }

    /**
     * 内部実行用API定義メソッド: CSRFトークンとホスト名のチェックを行う
     * - フロントエンドJavaScriptからAjax通信で実行する場合などに使う
     */
    public static function cmd($method, $route, callable $callback)
    {
        $proc = function (Request $request, Response $response, array $args) use ($callback) {
            // only accept json data
            $json = json_decode($request->getBody(), true);
            // check csrf token & host name
            $check = self::checkCsrf($json);
            if (!self::checkCsrf($json) || $request->getUri()->getHost() !== $_SERVER['SERVER_NAME']) {
                return $response->withStatus(403); // Forbidden error
            }
            // callback
            $res = $callback($request, $response, $args, $json? $json: []);
            if (is_array($res)) {
                // return json data
                $response->getBody()->write(json_encode($res));
                return $response->withHeader('Content-Type', 'application/json');
            }
            return $res;
        };
        if (is_array($method)) {
            return self::$app->map($method, $route, $proc);
        }
        return self::$app->$method($route, $proc);
    }

    /**
     * API IP制限: config.yml/api.ips の設定に従ってチェックする
     * @return bool true => 許可されているIP or 制限なし, false => 許可されていないIP
     */
    private static function checkIPs()
    {
        if (is_array(CONFIG['api']['accept_ips'])) {
            if (in_array(self::getClientIp(), CONFIG['api']['accept_ips'])) {
                return true;
            }
            return false;
        }
        if (CONFIG['api']['accept_ips'] === true) {
            // no limit
            return true;
        }
        // all denied
        return false;
    }

    /**
     * API CSRFチェック
     * @return bool true => CSRF一致, false => CSRF不一致
     */
    private static function checkCsrf(array $json)
    {
        if (!isset($_SESSION['csrf_token']) || !isset($json['csrf']) || $_SESSION['csrf_token'] !== $json['csrf']) {
            return false;
        }
        return true;
    }
}
