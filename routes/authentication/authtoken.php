<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->post($container['prefix'].'/authtoken', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();

	$params = $request->getParsedBody();
	if (!array_key_exists("username", $params)) return response(false);
	if (!array_key_exists("password", $params)) return response(false);

	$type = false;
	if(strpos($params['username'], '@') !== false) {
		$type = 'email';
	}
	if (is_numeric($params['username'])) {
		$type = 'mobilelogin';
	} else {
		$type = 'username';
	}
	$input = $params['username'];

	$user = $userService->getUserByType($input, $type, false, false);
	if (!$user) return response(false);

	$salt     = $user->salt;
    $password = md5($params['password'] . $salt);
    if ($password == $user->password) {
		unset($user->password);
		unset($user->salt);
		unset($user->verification_code);
		
    	if($user && $user->activation) {
			unset($user->activation);
    		$result = [
    			'status' => false,
    			'validation' => $user
    		];
            return response($result);
        }
        $token_code = md5(($user->username).uniqid());

        $token = new Token();
        $token->data->token = $token_code;
		$token->data->user_id = $user->id;
		$token->data->session_id = session_id();

        if ($token->insert()) {
			$_SESSION["OSSN_USER"] = $user;
			$_SESSION["TOKEN"] = $token_code;
			return response(["token" => $token_code]);
        }
    }
	return response(false);
})->setName('authtoken');