<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/notification', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$userService = UserService::getInstance();
	$groupService = GroupService::getInstance();
	$eventService = EventService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	if (!array_key_exists('notify_token', $params)) $params['notify_token'] = false;
	if (!array_key_exists('type', $params)) $params['type'] = "user";

	if ($params['notify_token']) {
		$tokenService = TokenService::getInstance();
		$tokenService->updateNotifyToken($params['notify_token'], $loggedin_user->id, $params['type']);
	}
	$owner_id = false;
	switch ($params['type']) {
		case 'user':
			$owner_id = $loggedin_user->id;
			$owner_type = 'user';
			break;
		case 'store':
			if ($loggedin_user->chain_store) {
				$owner_id = $loggedin_user->chain_store;
			}
			$owner_type = 'store';
			break;
		default:
			break;
	}
	if (!$owner_id) return response(false);
	$notifications = $notificationService->getNotificationsByType($owner_id, $owner_type, $params['offset'], $params['limit']);

	if (!$notifications) return response(false);

	$events_id = $users_id = $groups_id = [];
	foreach ($notifications as $key => $notification) {
		switch ($notification->from_type) {
			case 'user':
				array_push($users_id, $notification->from_id);
				break;
			case 'group':
				array_push($groups_id, $notification->from_id);
				break;
			case 'event':
				array_push($events_id, $notification->from_id);
				break;
			default:
				# code...
				break;
		}
	}

	if ($users_id) {
		$users_id = implode(',', $users_id);
		$users = $userService->getUsersByType($users_id, 'id', false);
	}

	foreach ($notifications as $key => $notification) {
		switch ($notification->from_type) {
			case 'user':
				foreach ($users as $user) {
					if ($notification->from_id == $user->id) {
						$notification->from = $user;
						$notification->from_avatar = $user->avatar;
					}
				}
				break;
			case 'group':
				if ($groups_id) {
					$groups_id = implode(',', $groups_id);
					$groups = $groupService->getGroupsById($groups_id, 'id', 0, 9999999999);
					if ($groups) {
						foreach ($groups as $group) {
							if ($notification->from_id == $group->id) {
								$notification->from = $group;
								$notification->from_avatar = $group->avatar;
							}
						}
					}
				}
				break;
			case 'event':
				if ($events_id) {
					$events_id = implode(',', $events_id);
					$events = $eventService->getEventsByType($events_id, 'id', 0, 999999999);
					if ($events) {
						foreach ($events as $event) {
							if ($notification->from_id == $event->id) {
								$notification->from = $event;
								$notification->from_avatar = $event->avatar;
							}
						}
					}
				}
				break;
			default:
				# code...
				break;
		}
		$notifications[$key] = $notification;
	}
	return response($notifications);
});