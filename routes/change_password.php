<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->patch($container['prefix'].'/change_password', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();
	if (!$params) $params = [];
	if (!array_key_exists('old_password', $params)) $params['old_password'] = false;
	if (!array_key_exists('new_password', $params)) $params['new_password'] = false;
	$user_params = null;
	$user_params[] = [
		'key' => 'id',
		'value' => "= {$loggedin_user->id}",
		'operation' => ''
	];
	$user = $userService->getUser($user_params, true, false);
	if (!$user) return response(false);

	$salt = $user->salt;
    $password = md5($params['old_password'] . $salt);
    if ($password == $user->password) {
    	$salt = substr(uniqid(), 5);
		$password = md5($params['new_password'] . $salt);

		$user = new User;
		$user->data->password = $password;
		$user->data->salt = $salt;	
		$user->where = "id = {$loggedin_user->id}";
		return response($user->update());
    }
    return response(false);
});