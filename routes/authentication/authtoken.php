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
	if (!array_key_exists("type", $params)) $params['type'] = 'user';

	$type = false;
	$user_params = null;
	if(strpos($params['username'], '@') !== false) {
		$type = 'email';
		$user_params[] = [
			'key' => 'email',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
	}
	if (is_numeric($params['username'])) {
		$type = 'mobilelogin';
		$user_params[] = [
			'key' => 'mobilelogin',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
	} else {
		$type = 'username';
		$user_params[] = [
			'key' => 'username',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
	}
	$input = $params['username'];
	if ($params['type'] == 'shop') {
		$user_params[] = [
			'key' => 'chain_store',
			'value' => "<> ''",
			'operation' => 'AND'
		];
	}
	
	$user = $userService->getUser($user_params, true, false);
	// $user = $userService->getUserByType($input, $type, false, false);


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
        $tokenService = TokenService::getInstance();
        if ($tokenService->save($token_code, $user->id, $params['type'])) {
        	$_SESSION["OSSN_USER"] = $user;
			$_SESSION["TOKEN"] = $token_code;
			$userService->login($user->username);
			return response(["token" => $token_code]);
        }
    }
	return response(false);
})->setName('authtoken');