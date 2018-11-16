<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->patch($container['prefix'].'/upload_avatar_cover', function (Request $request, Response $response, array $args) {
	global $settings;
	$services = Services::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) return response(false);
	if (!array_key_exists('owner_type', $params)) return response(false);
	if (!array_key_exists('images', $params)) return response(false);
	if (!array_key_exists('image_type', $params)) return response(false);

	$type = $params['type'];
	$owner_id = $params['owner_id'];
	$images = $params['images'];


	switch ($type) {
		case 'user':
			if ($loggedin_user->id != $owner_id) return response(false);
			$services = Services::getInstance();
			$services->downloadImage($owner_id, $type, $params['image_type'], $params['images']);
			break;
		case 'group':
			$groupService = GroupService::getInstance();
			$group = $groupService->getGroupById($owner_id);
			if (!$group) return response(false);
			if ($group->owner_id != $loggedin_user->id) return response(false);
			$services = Services::getInstance();
			$services->downloadImage($owner_id, $type, $params['image_type'], $params['images']);
		case 'event':
		case 'business':
		case 'shop':
		default:
			# code...
			break;
	}
	return response(true);
});