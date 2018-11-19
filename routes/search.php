<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/search', function (Request $request, Response $response, array $args) {
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('text_search', $params))  	return response(false);
	if (!array_key_exists('type', $params))  	return response(false);

	switch ($params['type']) {
		case 'mobile':
			$mobile = preg_replace("/^\\+?84/i", "0", $params['text_search']);
			$userService = UserService::getInstance();
			$user = $userService->getUserByType($mobile, 'mobilelogin', false);
			if (!$user) return response(false);
			return response(["user" => $user]);
			break;
		default:
			return response(false);
			break;
	}
});