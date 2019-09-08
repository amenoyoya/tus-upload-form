<?php

namespace Slim\Framework;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// login api
Application::api('post', '/api/login/', function (Request $request, Response $response, array $args, array $json) {
    if (!isset($json['username']) || !isset($json['password'])) {
        return ['auth' => false, 'message' => 'Invalid parameters'];
    }
    $users = Model\User::find('all', ['conditions' => ['name' => $json['username']]]);
    if (count($users) > 0 && password_verify($json['password'], $users[0]->password)) {
        // tokenを発行しセッションに保存
        $authToken = bin2hex(openssl_random_pseudo_bytes(16));
        $_SESSION['auth_token'] = $authToken;
        $_SESSION['auth_username'] = $users[0]->name;
        // tokenとログインユーザー名を返す
        return ['auth' => true, 'token' => $authToken, 'username' => $users[0]->name, 'message' => 'Login as admin'];
    }
    return ['auth' => false, 'message' => 'Invalid username or password'];
});

// login confirm api
Application::api('post', '/api/auth/', function (Request $request, Response $response, array $args, array $json) {
    if (!isset($_SESSION['auth_token']) || empty($json['auth_token'])) {
        return ['auth' => false, 'message' => 'Not authenticated yet'];
    }
    if ($_SESSION['auth_token'] !== $json['auth_token']) {
        return ['auth' => false, 'message' => 'Authentication timed out'];
    }
    return ['auth' => true, 'message' => 'Authenticated'];
});

// get session login info api
Application::api('post', '/api/auth/session/', function (Request $request, Response $response, array $args, array $json) {
    if (isset($_SESSION['auth_token']) && isset($_SESSION['auth_username'])) {
        return ['token' => $_SESSION['auth_token'], 'username' => $_SESSION['auth_username']];
    }
    // 基本的に一回しか実行しないように、セッション保存がない場合は適当な token を返す
    return ['token' => 'null', 'username' => ''];
});

// log out api
Application::api('post', '/api/logout/', function (Request $request, Response $response, array $args, array $json) {
    if (isset($_SESSION['auth_token'])) {
        unset($_SESSION['auth_token']);
    }
    if (isset($_SESSION['auth_username'])) {
        unset($_SESSION['auth_username']);
    }
    return ['token' => 'null', 'username' => ''];
});

// sign up api
Application::api('post', '/api/signup/', function (Request $request, Response $response, array $args, array $json) {
    if (!isset($json['username']) || !isset($json['password'])
        || strlen($json['username']) > 15 || strlen($json['password']) > 30
    ) {
        return ['reg' => false, 'message' => 'Invalid parameters'];
    }
    $users = Model\User::find('all', ['conditions' => ['name' => $json['username']]]);
    if (count($users) > 0) {
        return ['reg' => false, 'message' => "User '{$json['username']}' already exists"];
    }
    // register
    $date = new \DateTime();
    $user = new Model\User();
    $user->name = $json['username'];
    $user->password = password_hash($json['password'], PASSWORD_BCRYPT);
    if ($user->save()) {
        return ['reg' => true, 'message' => "User '{$user->name}' registered"];
    }
    return ['reg' => false, 'message' => 'Database error occured'];
});

// request info
Application::get('/request/', function (Request $request, Response $response, array $args) {
    $html = '';
    foreach ($request->getHeaders() as $name => $values) {
        $html .= '<dt>' . $name . '</dt><dd>' . implode(', ', $values) . '</dd>';
    }
    $response->getBody()->write("<dl>{$html}</dl>");
    return $response;
});
