<?php

namespace Slim\Framework;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Symfony\Component\Yaml\Yaml;

// load composer libraries
require_once __DIR__ . '/vendor/autoload.php';

// configure from yaml
define('CONFIG', Yaml::parse(file_get_contents(__DIR__ . '/config.yml')));

// load slim framework wrapper library
require_once __DIR__ . '/app/app.php';

// use database
if (CONFIG['db']['use']) {
    define('DB', Yaml::parse(file_get_contents(CONFIG['db']['config_file'])));

    /**
     * MySQL 5.6 だと datetime のフォーマットにタイムゾーンが入っているとエラーになるため修正
     * 参考: https://github.com/langrid/langrid-php-library/issues/10
     */
    \ActiveRecord\Connection::$datetime_format = 'Y-m-d H:i:s';

    \ActiveRecord\Config::initialize(function($cfg) {
        $env = DB['environments'];
        // $cfg->set_model_directory(__DIR__ . '/' . DB['paths']['models']);
        $cfg->set_connections([
           'local' => "{$env['local']['adapter']}://{$env['local']['user']}:{$env['local']['pass']}@{$env['local']['host']}:{$env['local']['port']}/{$env['local']['name']}",
           'development' => "{$env['development']['adapter']}://{$env['development']['user']}:{$env['development']['pass']}@{$env['development']['host']}:{$env['development']['port']}/{$env['development']['name']}",
           'production' => "{$env['production']['adapter']}://{$env['production']['user']}:{$env['production']['pass']}@{$env['production']['host']}:{$env['production']['port']}/{$env['production']['name']}"
        ]);
        $cfg->set_default_connection($env['default_database']);
    });

    // php-activerecord の Model オートロードを使うと namespace が上手く解決できないため手動で load
    $dir = __DIR__ . '/' . DB['paths']['models'];
    foreach (scandir($dir) as $file) {
        $path = realpath("$dir/$file");
        $ext = substr($path, strrpos($path, '.'));
        if (strcasecmp($ext, '.php') === 0) {
            require_once $path;
        }
    }
}

// use session for authentication
session_start();

// create slim framework app
Application::create();

// home routing
Application::get('/', function (Request $request, Response $response, array $args) {
    if (!isset($_SESSION['csrf_token'])) {
        // generate csrf token
        $csrfToken = bin2hex(openssl_random_pseudo_bytes(16));
        // save csrf token to session
        $_SESSION['csrf_token'] = $csrfToken;
    }
    $response->getBody()->write(sprintf(CONFIG['web']['home_html'], $_SESSION['csrf_token']));
    return $response;
});

// load all api
require_once __DIR__ . '/app/api.php';

Application::execute();
