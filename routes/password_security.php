<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/password_security', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();	
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("password", $params)) $params["password"] = false;
	if (!$params['password']) return response(false);

	$password = $params['password'];
	$user_params[] = [
		'key' => 'username',
		'value' => "= '{$loggedin_user->username}'",
		'operation' => ''
	];
	$user = $userService->getUserByType($loggedin_user->username, 'username', false, false);
	
	$password = md5($password . $user->salt);

	if ($password == $user->password) {
		return response(true);
	}
	return response(false);
});