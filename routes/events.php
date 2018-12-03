<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$eventService = EventService::getInstance();
	$userService = UserService::getInstance();
	$time = time();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists("event_id", $params)) return response(false);

	$event = $eventService->getEventByType($params['event_id'], 'id');
	if (!$event) return response(false);

	$owners = $userService->getUsersByType($event->owners_id, 'id', false);
	$event->owners = $owners;

	if ($event->invites_id) {
		$invites = $userService->getUsersByType($event->invites_id, 'id', false);
		$event->invites = $invites;
	}

	if ($event->end_date < $time) {
		$event->history = true;
	}

	return response($event);
});

$app->post($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$eventService = EventService::getInstance();
	$relationshipService = RelationshipService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$time = time();
	$data = $users_guid = $users = $event_params = [];
	$types = "";

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('limit', $params))	 	$params['limit'] = 10;
	if (!array_key_exists('offset', $params))	 	$params['offset'] = 0;
	if (!array_key_exists('event_type', $params))	$params['event_type'] = "myself";
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];
	$event_type = $params['event_type'];

	$event_params = null;
	$event_params[] = [
		'key' => 'status',
		'value' => "<> 2",
		'operation' => ''
	];

	switch ($event_type) {
		case 'myself':
			$event_params[] = [
				'key' => "FIND_IN_SET({$loggedin_user->id}, owners_id)",
				'value' => '',
				'operation' => 'AND'
			];
			$event_params[] = [
				'key' => 'end_date',
				'value' => "> {$time}",
				'operation' => 'AND'
			];
			$events = $eventService->getEvents($event_params, $offset, $limit);
			break;
		case 'guest':
			$events_id = [];
			$relations = $relationshipService->getRelationsByType(false, $loggedin_user->id, 'event:approve', $offset = 0, $limit = 10);
			if ($relations) {
				foreach ($relations as $key => $relation) {
					array_push($events_id, $relation->relation_to);
				}
			}
			$relation_params = null;
			$relation_params[] = [
				'key' => 'type',
				'value' => "= 'event:approve'",
				'operation' => ''
			];
			$relation_params[] = [
				'key' => 'relation_to',
				'value' => "= {$loggedin_user->id}",
				'operation' => 'AND'
			];

			if ($events_id) {
				$events_id = implode(',', $events_id);
				$relation_params[] = [
					'key' => 'relation_to',
					'value' => "NOT IN ($events_id)",
					'operation' => 'AND'
				];
			}
			$relations = $relationshipService->getRelations($relation_params, 0, 99999999);
			$events_id = [];
			if ($relations) {
				foreach ($relations as $key => $relation) {
					array_push($events_id, $relation->relation_to);
				}
			}

			if (!$events_id) return response(false);
			$events_id = implode(',', $events_id);

			$event_params[] = [
				'key' => 'end_date',
				'value' => "> {$time}",
				'operation' => 'AND'
			];
			$event_params[] = [
				'key' => 'id',
				'value' => "IN {$events_id}",
				'operation' => 'AND'
			];
			$events = $eventService->getEvents($event_params, $offset, $limit);

			break;
		case 'history':
			$event_params[] = [
				'key' => 'end_date',
				'value' => "< {$time}",
				'operation' => 'AND'
			];
			$event_params[] = [
				'key' => "FIND_IN_SET({$loggedin_user->id}, owners_id)",
				'value' => '',
				'operation' => 'AND'
			];

			$events = $eventService->getEvents($event_params, $offset, $limit);
			break;
		default:
			# code...
			break;
	}

	if (!$events) return response(false);
	$owners_id = [];
	foreach ($events as $key => $event) {
		array_push($owners_id, $event->creator_id);
	}
	$owners_id = array_unique($owners_id);
	$owners_id = implode(',', $owners_id);

	$owners = $userService->getUsersByType($owners_id, 'id', false);

	foreach ($events as $key => $event) {
		foreach ($owners as $key => $owner) {
			if ($event->creator_id == $owner->id) {
				$event->owners = array($owner);
			}
		}
		$events[$key] = $event;
	}
	return response(array_values($events));
});

$app->put($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$eventService = EventService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('title', $params))	 	return response(false);
	if (!array_key_exists('description', $params))	 	$params['description'] = false;
	if (!array_key_exists('start_date', $params))	return response(false);
	if (!array_key_exists('end_date', $params))	 	return response(false);
	if (!array_key_exists('country', $params))	 	$params['country'] = false;
	if (!array_key_exists('location', $params))	 	$params['location'] = false;
	if (!array_key_exists('template', $params))	 	$params['template'] = false;
	if (!array_key_exists('has_inventory', $params))	 	$params['has_inventory'] = 1;
	if (!array_key_exists('status', $params))	 	$params['status'] = 1;
	if (!array_key_exists('event_type', $params))	 	$params['event_type'] = false;
	if (!array_key_exists('owners_id', $params))	 	$params['owners_id'] = [];
	if (!array_key_exists('invites_id', $params))	 	$params['invites_id'] = [];

	$event_params = null;
	$event_params['owner_id'] = $loggedin_user->id;
	$event_params['type'] = 'user';
	$event_params['title'] = $params['title'];
	$event_params['description'] = $params['description'];
	if (is_array($params['owners_id'])) {
		if (!in_array($loggedin_user->id, $params['owners_id'])) {
			array_push($params['owners_id'], $loggedin_user->id);
		}
	} else {
		array_push($params['owners_id'], $loggedin_user->id);
	}
	$event_params['owners_id'] = array_unique($params['owners_id']);
	$event_params['owners_id'] = implode(',', $event_params['owners_id']);

	if (is_array($params['invites_id'])) {
		$event_params['invites_id'] = array_unique($params['invites_id']);
		$event_params['invites_id'] = implode(',', $event_params['invites_id']);	
	}
	$event_params['start_date'] = strtotime($params['start_date']);
	$event_params['end_date'] = strtotime($params['end_date']);
	$event_params['country'] = $params['country'];
	$event_params['location'] = $params['location'];
	$event_params['template'] = $params['template'];
	$event_params['has_inventory'] = $params['has_inventory'];
	$event_params['status'] = $params['status'];
	$event_params['creator_id'] = $loggedin_user->id;
	$event_params['friendly_url'] = $params['friendly_url'];
	$event_params['published'] = 0;
	return response($eventService->save($event_params));
});

$app->patch($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$eventService = EventService::getInstance();
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) return response(false);
	if (!array_key_exists('type', $params)) $params['type'] = false;
	if (!array_key_exists('status', $params))	 	$params['status'] = 1;

	// if (!array_key_exists('title', $params))	 	$params['title'] = 10;
	// if (!array_key_exists('description', $params))	 	$params['description'] = 10;

	// if (!array_key_exists('start_date', $params))	 	$params['start_date'] = 10;
	// if (!array_key_exists('end_date', $params))	 	$params['end_date'] = 10;
	// if (!array_key_exists('country', $params))	 	$params['country'] = 10;
	// if (!array_key_exists('location', $params))	 	$params['location'] = 10;
	// if (!array_key_exists('template', $params))	 	$params['template'] = 10;
	// if (!array_key_exists('has_inventory', $params))	 	$params['has_inventory'] = 1;
	// if (!array_key_exists('event_type', $params))	 	$params['event_type'] = 10;
	// if (!array_key_exists('owners', $params))	 	$params['owners_id'] = false;
	// if (!array_key_exists('invites', $params))	 	$params['invites_id'] = false;

	$event = $eventService->getEventByType($params['id'], 'id');
	if ($event->status != 2) return response(false);

	if ($params['type'] == 'publish') {
		if ($event->published) return response(false);
		if ($event->invites_id) {
			$invites_id = explode(',', $event->invites_id);
			$invites = $userService->getUsersByType($invites_id, 'id');
			foreach ($invites as $key => $invite) {
				$relationshipService->save($invite, $event, 'event:invitation');
			}
		}
		$event = object_cast("Event", $event);
		$event->data->id = $event->id;
		$event->data->published = 1;
		$event->data->status = 1;
		return $event->update(true);
	}

	$event = object_cast("Event", $event);
	$event->data->id = $event->id;
	$event->data->status = 1;
	return $event->update(true);

});

$app->delete($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$eventService = EventService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists("event_id", $params)) return response(false);

	$event = $eventService->getEventByType($params['event_id'], 'id');
	if ($loggedin_user->id != $event->creator_id) return response(false);

	$event = object_cast("Event", $event);
	$event->data->status = 2;
	$event->data->id = $event->id;
	$event->where = "id = {$event->id}";
	return $event->update(true);
});