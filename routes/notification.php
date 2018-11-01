<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/notification', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	if (!array_key_exists('notify_token', $params)) $params['notify_token'] = false;
	if (!array_key_exists('type', $params)) $params['type'] = "user";

	if ($params['notify_token']) {
		$tokenService = TokenService::getInstance();
		$tokenService->updateNotifyToken($params['notify_token'], $loggedin_user->id, "user");
	}

	$notifications = $notificationService->getNotificationsByType($loggedin_user->id, 'owner_id', $params['offset'], $params['limit']);
	if (!$notifications) return response(false);

	$users_id = $groups_id = [];
	foreach ($notifications as $key => $notification) {
		switch ($notification->from_type) {
			case 'user':
				array_push($users_id, $notification->from_id);
				break;
			case 'group':
				array_push($groups_id, $notification->from_id);
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

	if ($groups_id) {
		$groups_id = implode(',', $groups_id);
		$groups = $userService->getUsersByType($groups_id, 'id');
	}

	foreach ($notifications as $key => $notification) {
		switch ($notification->from_type) {
			case 'user':
				foreach ($users as $user) {
					if ($notification->from_id == $user->id) {
						$notification->from = $user;
					}
				}
				break;
			case 'group':
				foreach ($groups as $group) {
					if ($notification->from_id == $group->id) {
						$notification->from = $group;
					}
				}
				break;
			default:
				# code...
				break;
		}
		$notifications[$key] = $notification;
	}
	return $notifications;
});