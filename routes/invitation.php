<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});

$app->post($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	$invitation_type = ["user", "group", "event"];
	$event_requests = $group_requests = $user_requests = [];
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('limit', $params))	 	$params['limit'] = 10;
	if (!array_key_exists('offset', $params))	 	$params['offset'] = 0;
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];

	$users = $groups = $events = [];
	foreach ($invitation_type as $key => $type) {
		switch ($type) {
			case 'user':
				$user_requests = getInvitation("friend:request", "friend:request", $loggedin_user->guid);
				break;
			case 'group':
				$group_requests = getInvitation("group:invite", "group:invite:approve", $loggedin_user->guid, true);
				break;
			case 'event':
				$invite_requests = getInvitation("event:invite", "event:invite:approve", $loggedin_user->guid, true);
				$member_requests = getInvitation("event:member", "event:member:approve", $loggedin_user->guid, true);
				break;
		}
	}
	$users_result = [];
	if ($user_requests) {
		$user_requests = array_unique($user_requests);
		$users_guid = implode(',', array_unique($user_requests));
		$user_params = null;
		$user_params[] = [
			'key' => 'guid',
			'value' => "IN ({$users_guid})",
			'operation' => ''
		];
		$users = $select->getUsers($user_params,0,999999999);
		foreach ($users as $key => $user) {
			$users_result[$user->guid] = $user;
		}
	}

	$groups_result = [];
	if ($group_requests) {
		$group_requests = array_unique($group_requests);
		$groups_guid = implode(',', array_unique($group_requests));
		$group_params = null;
		$group_params[] = [
			'key' => 'guid',
			'value' => "IN ({$groups_guid})",
			'operation' => ''
		];
		$groups = $select->getGroups($group_params,0,999999999);
		foreach ($groups as $key => $group) {
			$groups_result[$group->guid] = $group;
		}
	}

	if ($event_requests) {
		$event_requests = array_unique(array_merge($invite_requests, $member_requests));
		$event_requests = implode(',', array_unique($event_requests));
		$event_params = null;
		$event_params[] = [
			'key' => 'guid',
			'value' => "IN ({$event_requests})",
			'operation' => ''
		];
		$events = $select->getEvents($event_params,0,9999999999);
	}

	return [
		"users" => array_values($users),
		"groups" => array_values($groups),
		"events" => array_values($events)
	];
	return response(false);
});