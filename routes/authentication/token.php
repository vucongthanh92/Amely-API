<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/token', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('token', $params))  	$params['token'] = false;

	if (!$params['token']) return response(false);

	$user = new User();
	$user->data->notify_token = $params['token'];
	$user->where = "id = {$loggedin_user->id}";
	return response($user->update());
});

$app->delete($container['prefix'].'/token', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();

	$user = new User();
	$user->data->notify_token = "";
	$user->where = "id = {$loggedin_user->id}";
	return response($user->update());
});