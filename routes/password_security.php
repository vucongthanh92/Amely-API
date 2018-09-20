<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/password_security', function (Request $request, Response $response, array $args) {

	$select = SlimSelect::getInstance();
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
	$loggedin_user = $select->getUsers($user_params, 0, 1, true, false);
	$password = md5($password . $loggedin_user->salt);

	if ($password == $loggedin_user->password) {
		return response(true);
	}
	return response(false);
});