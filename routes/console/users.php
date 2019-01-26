<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/console_users', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$time = time();

	$users = $userService->getUsers(null, 0, 999999999);
	if ($users) {
		foreach ($users as $key => $user) {
			$user = object_cast("User", $user);
			$user->data->gift_count = 0;
			$user->data->offer_count = 0;
			$user->where = "id = {$user->id}";
			$user->update();
		}
	}
	return response(true);
})->setName('console_users');
