<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/logout', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params))  	$params['type'] = 'user';

	$tokenService = TokenService::getInstance();
	$token = $tokenService->getTokenByType($loggedin_user->id, $params['type']);

	$token = object_cast("Token", $token);
	$token->where = "id = {$token->id}";
	$token->delete();

	return response(true);
});