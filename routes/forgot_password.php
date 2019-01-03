<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/forgot_password', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('mobile', $params))  		$params['mobile'] = false;
	if (!array_key_exists('username', $params))  		$params['username'] = false;
	if (!array_key_exists('new_password', $params))  		$params['new_password'] = false;

	$user = $userService->getUserByType($params['username'], 'username', false);
	if (!$user) return response(false);
	if ($user->mobilelogin != $params['mobile']) return response(false);
	
	$salt = substr(uniqid(), 5);
	$password = md5($params['new_password'] . $salt);

	$user = object_cast("User", $user);
	$user->data->password = $password;
	$user->data->salt = $salt;
	$user->data->id = $user->id;
	$user->where = "id = {$user->id}";
	return response($user->update(true));
})->setName('forgot_password');

$app->put($container['prefix'].'/forgot_password', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('mobile', $params))  		$params['mobile'] = false;
	if (!array_key_exists('username', $params))  		$params['username'] = false;

	$user = $userService->getUserByType($params['username'], 'username', false);
	if (!$user) return response(false);
	if ($user->mobilelogin != $params['mobile']) return response(false);

	$code = rand(100000, 999999);
	$user = object_cast("User", $user);
	$user->data->verification_code = $code;
	$user->where = "id = {$user->id}";
	if ($user->update(true)) {
		return response(Services::getInstance()->sendByMobile($user->mobilelogin, $code));
	}
	return response(false);
})->setName('forgot_password');

