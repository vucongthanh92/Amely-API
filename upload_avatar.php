<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->patch($container['prefix'].'/upload_avatar', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);
	if (!array_key_exists('image', $params)) return response(false);

	$type = $params['type'];
	$owner_id = $params['owner_id'];
	$image = $params['image'];

	switch ($type) {
		case 'user':
			if ($loggedin_user->id == $owner_id) {
				
			}
			break;
		
		default:
			# code...
			break;
	}
});