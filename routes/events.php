<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});

$app->post($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$eventService = EventService::getInstance();
	$loggedin_user = loggedin_user();
	$time = time();
	$data = $users_guid = $users = $event_params = [];
	$types = "";

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('limit', $params))	 	$params['limit'] = 10;
	if (!array_key_exists('offset', $params))	 	$params['offset'] = 0;
	if (!array_key_exists('event_type', $params))	$params['event_type'] = "all";
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];
	$event_type = $params['event_type'];
	return response(false);
	// $relation_params = null;

	// switch ($event_type) {
	// 	case 'publish':
	// 		/*
	// 		if (!array_key_exists('event_guid', $params))	$params['event_guid'] = 0;
	// 		$event_guid = (int)$params['event_guid'];
	// 		$event_params = null;
	// 		$event_params[] = [
	// 			'key' => 'guid',
	// 			'value' => "= {$events_guid}",
	// 			'operation' => ''
	// 		];
	// 		$event_params[] = [
	// 			'key' => 'status',
	// 			'value' => "<> 1",
	// 			'operation' => 'AND'
	// 		];
	// 		$event_params[] = [
	// 			'key' => 'published',
	// 			'value' => "<> 2",
	// 			'operation' => 'AND'
	// 		];
	// 		$event = $select->getEvents($event_params, 0, 1);
	// 		if (!$event) return response(false);
	// 		$invites = json_decode($event->invites);
	// 		foreach ($invites as $invite) {
	// 			ossn_add_relation($invite, $event_guid, 'event:invite');
	// 			(new OssnNotifications)->addNotification("event:invite", $loggedin_user->guid, $invite, $event_guid, 0);
	// 		}
	// 		$event->data->published = 2;
	// 		return $event->save();
	// 		*/
	// 		return response(false);
	// 		break;
	// 	case 'all':
	// 		$relation_params[] = [
	// 			'key' => 'type',
	// 			'value' => "IN ('event:join:approve','event:member:approve','event:invite:approve')",
	// 			'operation' => ''
	// 		];
	// 		break;
	// 	case 'member':
	// 		$relation_params[] = [
	// 			'key' => 'type',
	// 			'value' => "IN ('event:join:approve','event:member:approve')",
	// 			'operation' => ''
	// 		];
	// 		break;
	// 	case 'guest':
	// 		$relation_params[] = [
	// 			'key' => 'type',
	// 			'value' => "IN ('event:invite:approve')",
	// 			'operation' => ''
	// 		];
	// 		break;
	// 	case 'history':
	// 		$relation_params[] = [
	// 			'key' => 'type',
	// 			'value' => "IN ('event:join:approve','event:member:approve','event:invite:approve')",
	// 			'operation' => ''
	// 		];
	// 		break;
	// 	default:
	// 		return false;
	// 		break;
	// }
	// $relation_params[] = [
	// 	'key' => 'relation_to',
	// 	'value' => "= {$loggedin_user->guid}",
	// 	'operation' => 'AND'
	// ];
	// $relations = $select->getRelationships($relation_params, 0, 99999999);
	// if (!$relations) return response(false);
	// $events_guid = array_unique(array_map(create_function('$o', 'return $o->relation_from;'), $relations));
	// $events_guid = implode(",", $events_guid);

	// if (!$events_guid) return response(false);
	
	// $event_params[] = [
	// 	'key' => 'guid',
	// 	'value' => "IN ({$events_guid})",
	// 	'operation' => ''
	// ];

	// if ($event_type == "history") {
	// 	$event_params[] = [
	// 		'key' => 'end_date',
	// 		'value' => "<= {$time}",
	// 		'operation' => 'AND'
	// 	];
	// } else {
	// 	$event_params[] = [
	// 		'key' => 'end_date',
	// 		'value' => "> {$time}",
	// 		'operation' => 'AND'
	// 	];
	// }

	// //check user is blocked
	// if (property_exists($loggedin_user, 'blockedusers')) {
	// 	$block_list = json_decode($loggedin_user->blockedusers);
	// 	if (property_exists($loggedin_user, 'blockedusers')) {
	// 		$block_list = json_decode($loggedin_user->blockedusers);
	// 		if (is_array($block_list) && count($block_list) > 0) {
	// 			$block_users = implode(',', $block_list);
	// 			$event_params[] = [
	// 				'key' => 'creator_guid',
	// 				'value' => "NOT IN ({$block_users})",
	// 				'operation' => 'AND'
	// 			];
	// 		}
	// 	}
	// }

	// $event_params[] = [
	// 	'key' => 'guid',
	// 	'value' => "DESC",
	// 	'operation' => 'order_by'
	// ];

	// $events = $select->getEvents($event_params, $offset, $limit);
	// if (!$events) return response(false);
	// foreach ($events as $key => $event) {
	// 	if ($event->type == "user") {
	// 		array_push($users_guid, $event->owner_guid);
	// 		$event->owner_user = $event->owner_guid;
	// 	} else {
	// 		array_push($users_guid, $event->creator_guid);
	// 		$event->owner_user = $event->owner_guid;
	// 	}

	// 	$members = json_decode($event->members);
	// 	$members_guid = [];
	// 	if (is_array($members) && count($members) > 0) {
	// 		foreach ($members as $key => $member) {
	// 			if (property_exists($loggedin_user, 'blockedusers')) {
	// 				if (is_array($block_list) && count($block_list) > 0) {
	// 					if (!in_array($member, $block_list)) {
	// 						array_push($users_guid, $member);
	// 						$members_guid[] = $member;
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}

	// 	$invites = json_decode($event->invites);
	// 	$invites_guid = [];
	// 	if (is_array($invites) && count($invites) > 0) {
	// 		foreach ($invites as $key => $invite) {
	// 			if (property_exists($loggedin_user, 'blockedusers')) {
	// 				if (is_array($block_list) && count($block_list) > 0) {
	// 					if (!in_array($invite, $block_list)) {
	// 						array_push($users_guid, $invite);
	// 						$invites_guid[] = $invite;
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}

	// 	$event->members = implode(",", $members_guid);
	// 	$event->invites = implode(",", $invites_guid);
	// }

	// $users_guid = array_filter($users_guid);
	// $users_guid = implode(",", array_unique($users_guid));
	// $user_params = null;
	// $user_params[] = [
	// 	'key' => 'guid',
	// 	'value' => "IN ({$users_guid})",
	// 	'operation' => ''
	// ];
	// $users = $select->getUsers($user_params,0,99999999, false);

	// $users_result = [];
	// foreach ($users as $key => $user) {
	// 	$users_result[$user->guid] = $user;
	// }
    
	// return [
	// 	"events" => $events,
	// 	"users" => $users_result
	// ];
});

$app->put($container['prefix'].'/events', function (Request $request, Response $response, array $args) {
	$eventService = EventService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('title', $params))	 	$params['title'] = 10;
	if (!array_key_exists('description', $params))	 	$params['description'] = 10;
	if (!array_key_exists('start_date', $params))	 	$params['start_date'] = 10;
	if (!array_key_exists('end_date', $params))	 	$params['end_date'] = 10;
	if (!array_key_exists('country', $params))	 	$params['country'] = 10;
	if (!array_key_exists('location', $params))	 	$params['location'] = 10;
	if (!array_key_exists('template', $params))	 	$params['template'] = 10;
	if (!array_key_exists('has_inventory', $params))	 	$params['has_inventory'] = 10;
	if (!array_key_exists('status', $params))	 	$params['status'] = 10;
	if (!array_key_exists('event_type', $params))	 	$params['event_type'] = 10;
	if (!array_key_exists('owners', $params))	 	$params['owners'] = 10;
	if (!array_key_exists('members', $params))	 	$params['members'] = 10;
	if (!array_key_exists('invites', $params))	 	$params['invites'] = 10;


});