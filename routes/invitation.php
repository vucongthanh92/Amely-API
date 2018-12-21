<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$eventService = EventService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	// type (user group event business)
	if (!array_key_exists('type', $params)) 		$params['type'] = "user";
	if (!array_key_exists('owner_id', $params))	 	$params['owner_id'] = $loggedin_user->id;
	// khi type = user thi invitation_type (user group event business)
	if (!array_key_exists('invitation_type', $params)) 		$params['invitation_type'] = "user";

	$owners = [];
	
	switch ($params['type']) {
		case 'user':
			switch ($params['invitation_type']) {
				case 'user':
					# code...
					break;
				case 'event':
					$events_approve_id = [];

					$events_approve = $relationshipService->getRelationsByType($loggedin_user->id, false, 'event:approve', 0, 99999999);
					if ($events_approve) {
						foreach ($events_approve as $key => $events_approve) {
							array_push($events_approve_id, $events_approve->relation_from);
						}
					}
					$events_approve_id = array_unique($events_approve_id);
					$events_approve_id = implode(',', $events_approve_id);

					$relation_params[] = [
						'key' => 'type',
						'value' => "= 'event:invitation'",
						'operation' => ''
					];
					$relation_params[] = [
						'key' => 'relation_to',
						'value' => "NOT IN ($events_approve_id)",
						'operation' => 'AND'
					];
					$relation_params[] = [
						'key' => 'relation_from',
						'value' => "= $loggedin_user->id",
						'operation' => 'AND'
					];
					$events_invitation_id = [];
					$relations = $relationshipService->getRelations($relation_params, 0, 9999999);
					if (!$relations) return response(false);
					foreach ($relations as $key => $relation) {
						array_push($events_invitation_id, $relation->relation_to);
					}

					$events_invitation_id = array_unique($events_invitation_id);
					$events_invitation_id = implode(',', $events_invitation_id);

					$events = $eventService->getEventsByType($events_invitation_id, 'id', 0, 99999999);

					if (!$events) return response(false);
					foreach ($events as $key => $event) {
						array_push($owners, [
							'id' => $event->id,
							'type' => 'event',
							'title' => $event->title,
							'image' => $event->avatar
						]);
					}

					break;
				default:
					# code...
					break;
			}


			break;
		
		default:
			# code...
			break;
	}

	return response($owners);
});

$app->post($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params))  	$params['offset'] = 0;
	if (!array_key_exists('limit', $params))  	$params['limit'] = 10;

	$offset = $params['offset'];
	$limit = $params['limit'];

	$relationships = $relationshipService->getRelationsByType(false, $loggedin_user->id, 'friend:request', $offset, $limit);
	if (!$relationships) return response(false);
	foreach ($relationships as $key => $relationship) {
		if ($relationshipService->getRelationByType($relationship->relation_to, $relationship->relation_from, 'friend:request')) unset($relationships[$key]);
	}

	if (!$relationships) return response(false);
	$users_id = array_map(create_function('$o', 'return $o->relation_from;'), array_values($relationships));
	$users_id = implode(',', $users_id);
	$users = $userService->getUsersByType($users_id, 'id', false);

	return response($users);
});

$app->put($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$userService = UserService::getInstance();
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('from_id', $params))  	$params['from_id'] = $loggedin_user->id;
	if (!array_key_exists('to_id', $params))  		$params['to_id'] = false;
	if (!array_key_exists('type', $params))  		$params['type'] = false;

	if (!$params['from_id'] || !$params['to_id'] || !$params['type']) return response(false);
	$from = $params['from_id'];
	$tos = $params['to_id'];
	$type = $params['type'];

	$notify_params = null;

	switch ($type) {
		case 'user':
			$from = $loggedin_user->id;
			foreach ($tos as $key => $to) {
				$user = $userService->getUserByType($to, 'id');
				if ($relationshipService->getRelationByType($from, $to, 'friend:request')) {
					if ($relationshipService->getRelationByType($to, $from, 'friend:request')) return response(false);
					$relationshipService->save($user, $loggedin_user, 'friend:request', 'approval');
					$services->addFriendFB($loggedin_user, $user);
				}
				$relationshipService->save($loggedin_user, $user, 'friend:request', 'invitation');
			}
			break;
		case 'group':
			$groupService = GroupService::getInstance();
			$group = $groupService->getGroupByType($from, 'id');
			foreach ($tos as $key => $to) {
				if (!$relationshipService->getRelationByType($from, $to, 'group:approve')) {
					$user = $userService->getUserByType($to, 'id');
					$relationshipService->save($user, $group, 'group:invite');
					$relationshipService->save($group, $user, 'group:approve');

					$notify_params = null;
					$notify_params['from'] = $group;
					$notify_params['to'] = $user;
					$notificationService->save($notify_params, 'group:joined');
					
					$services->memberGroupFB($group->id, $user->username, 'add');
				}
			}
			return response(true);
			break;
		case 'event':
			# code...
			break;
		default:
			# code...
			break;
	}
	return response(true);
});

$app->delete($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {

});