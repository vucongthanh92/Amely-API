<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/logout', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();

	$user = new User();
	$user->data->notify_token = "";
	$user->where = "id = {$loggedin_user->id}";
	return response($user->update());
});