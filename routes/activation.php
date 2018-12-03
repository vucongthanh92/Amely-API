<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/activation', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('username', $params))  	$params['username'] = false;
	if (!array_key_exists('password', $params))  	$params['password'] = false;
	if (!array_key_exists('code', $params))  	$params['code'] = false;
	if (!array_key_exists('email', $params))  	$params['email'] = false;
	if (!array_key_exists('mobilelogin', $params))  	$params['mobilelogin'] = false;

	$type = false;
	$input = false;
	if ($params['email']) {
		$type = 'email';
		$input = $params['email'];
	}
	if ($params['mobilelogin']) {
		$type = 'mobilelogin';
		$input = $params['mobilelogin'];
	}
	if (!$input || !$type) return response(false);
	$user = $userService->getUserByType($input, $type, false, false);
	if (!$user) return response(false);
	$user = object_cast("User", $user);
	if ($user->verification_code == $params['code']) {
		if ($user->activation) {
			$user->data->activation = '';
		}
		$user->data->verification_code = '';
		$user->data->id = $user->id;
		$user->where = "id = {$user->id}";
		return response($user->update());
	}
	return response(false);
})->setName('activation');

$app->put($container['prefix'].'/activation', function (Request $request, Response $response, array $args) {

	$services = Services::getInstance();
	$userService = UserService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('email', $params))  		$params['email'] = false;
	if (!array_key_exists('mobilelogin', $params))  $params['mobilelogin'] = false;

	$type = false;
	$input = false;
	if ($params['email']) {
		$type = 'email';
		$input = $params['email'];
	}
	if ($params['mobilelogin']) {
		$type = 'mobilelogin';
		$input = $params['mobilelogin'];
	}
	if (!$input || !$type) return response(false);
	$user = $userService->getUserByType($input, $type, false, false);
	if (!$user) return response(false);
	$user = object_cast("User", $user);
	$code = rand(100000, 999999);
	$user->data->verification_code = $code;
	$user->data->id = $user->id;
	$user->where = "id = {$user->id}";
	if ($user) {
		if ($params['email']) {
			if ($services->sendByEmail($user->email, "AMELY", $code)) {
				return response($user->update());
			}
		}
		if ($params['mobilelogin']) {
			$mobilelogin = preg_replace("/^\\+?84/i", "0", $user->mobilelogin);
			if ($services->sendByMobile($mobilelogin, $code)) {
				return response($user->update());
			}
		}
	}
	return response(false);
})->setName('activation');