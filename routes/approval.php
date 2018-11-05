<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/approval', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('from_id', $params))  	$params['from_id'] = $loggedin_user->id;
	if (!array_key_exists('to_id', $params))  		$params['to_id'] = false;
	if (!array_key_exists('type', $params))  		$params['type'] = false;

	if (!$params['from_id'] || !$params['to_id'] || !$params['type']) return response(false);
	$from = $params['from_id'];
	$to = $params['to_id'];
	$type = $params['type'];
	switch ($type) {
		case 'user':
			$userService = UserService::getInstance();
			$from = $loggedin_user->id;
			$user = $userService->getUserByType($to, 'id');
			
			if ($relationshipService->getRelationByType($from, $to, 'friend:request')) {
				if ($relationshipService->getRelationByType($to, $from, 'friend:request')) return response(false);
				$relationshipService->save($user, $loggedin_user, 'friend:request', 'approval');
				$services->addFriendFB($loggedin_user, $user);
				return response(true);
			}
			$relationshipService->save($loggedin_user, $user, 'friend:request', 'approval');
			return response(true);
			break;
		case 'group':
			# code...
			break;
		case 'event':
			# code...
			break;
		default:
			# code...
			break;
	}
});

$app->delete($container['prefix'].'/approval', function (Request $request, Response $response, array $args) {

	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists('to_id', $params)) $params['to_id'] = false;
	if (!array_key_exists('type', $params)) $params['type'] = false;

	if (!$params['to_id'] || !$params['type']) return response(false);

	if ($params['type'] == 'user') {
    	return response($relationshipService->deleteFriend($loggedin_user->id, $params['to_id']));
	}

	return response(false);

});