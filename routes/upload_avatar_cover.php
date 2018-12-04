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

	$owner_type = $params['owner_type'];
	$owner_id = $params['owner_id'];
	$images = $params['images'];
	$image_type = $params['image_type'];


	switch ($owner_type) {
		case 'user':
			if ($loggedin_user->id != $owner_id) return response(false);
			$services->downloadImage($owner_id, $owner_type, $image_type, $images);
			break;
		case 'group':
			$groupService = GroupService::getInstance();
			$group = $groupService->getGroupById($owner_id);
			if (!$group) return response(false);
			if ($group->owner_id != $loggedin_user->id) return response(false);
			$services->downloadImage($owner_id, $owner_type, $image_type, $images);
			break;
		case 'event':
			$eventService = EventService::getInstance();
			$event = $eventService->getEventByType($owner_id, 'id');
			if (!$event) return response(false);
			if ($event->creator_id != $loggedin_user->id) return response(false);
			$services->downloadImage($owner_id, $owner_type, $image_type, $images);
			break;
		case 'business':
		case 'shop':
		default:
			# code...
			break;
	}
	return response(true);
});