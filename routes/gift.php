<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/gift', function (Request $request, Response $response, array $args) {
	$giftService = GiftService::getInstance();
	$userService = UserService::getInstance();
	$groupService = GroupService::getInstance();
	$eventService = EventService::getInstance();
	$businessService = BusinessService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('gift_id', $params)) $params['gift_id'] = 0;
	if (!$params['gift_id']) return response(false);

	$gift = $giftService->getGiftByType($params['gift_id'], 'id');
	if (!$gift) return response(false);
	$from_id = $gift->from_id;
	$from_type = $gift->from_type;
	$to_id = $gift->to_id;
	$to_type = $gift->to_type;
	$from_owner = $to_owner = false;
	switch ($from_type) {
		case 'user':
			$from_user = $userService->getUserByType($from_id, 'id');
			$gift->from_user = $from_user;
			break;
		case 'group':
			$from_group = $groupService->getGroupByType($from_id);
			unset($from_group->owners);
			$gift->from_group = $from_group;
			break;
		case 'event':
			$from_event = $eventService->getEventByType($from_id, 'id');
			$gift->from_event = $from_event;
			break;
		case 'business':
			$from_business = $businessService->getPageId($from_id);
			$gift->from_business = $from_business;
			break;
		default:
			# code...
			break;
	}

	switch ($to_type) {
		case 'user':
			$to_user = $userService->getUserByType($to_id, 'id');
			$gift->to_user = $to_user;
			break;
		case 'group':
			$to_group = $groupService->getGroupByType($to_id);
			unset($to_group->owners);
			$gift->to_group = $to_group;
			break;
		case 'event':
			$to_event = $eventService->getEventByType($to_id, 'id');
			$gift->to_event = $to_event;
			break;
		case 'business':
			$to_business = $businessService->getPageId($to_id);
			$gift->to_business = $to_business;
			break;
		default:
			# code...
			break;
	}
	
	$item = $itemService->getItemByType($gift->item_id);
	if (!$item) return response(false);
	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	$item->snapshot = $snapshot;
	$gift->item = $item;
	return response($gift);
});

$app->post($container['prefix'].'/gift', function (Request $request, Response $response, array $args) {
	$giftService = GiftService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('from_id', $params)) $params['from_id'] = $loggedin_user->id;
	if (!array_key_exists('from_type', $params)) $params['from_type'] = 'user';

	$gift_params = null;
	$gift_params[] = [
		'key' => 'from_id',
		'value' => "= {$params['from_id']}",
		'operation' => ''
	];
	$gift_params[] = [
		'key' => 'from_type',
		'value' => "= {$params['from_type']}",
		'operation' => 'AND'
	];

	$gifts = $giftService->getGifts($gift_params, 0, 999999999);
	if (!$gifts) return response(false);
	return response($gifts);
});

$app->put($container['prefix'].'/gift', function (Request $request, Response $response, array $args) {
	$giftService = GiftService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('from_id', $params)) $params['from_id'] = $loggedin_user->id;
	if (!array_key_exists('from_type', $params)) $params['from_type'] = 'user';
	if (!array_key_exists('to_id', $params)) $params['to_id'] = 0;
	if (!array_key_exists('to_type', $params)) $params['to_type'] = 0;
	if (!array_key_exists('item_id', $params)) $params['item_id'] = 0;
	if (!array_key_exists('quantity', $params)) $params['quantity'] = 0;
	if (!array_key_exists('message', $params)) $params['message'] = "";
	if (!$params['from_id'] || !$params['from_type'] || !$params['to_id'] || !$params['to_type'] || !$params['item_id'] || !$params['quantity']) return response(false);

	if ($params['to_type'] == 'user') {
		$userService = UserService::getInstance();
		$user = $userService->getUserByType($params['to_id'], 'username');
		if (!$user) return response(false);
		$params['to_id'] = $user->id;
	}
	$item = $itemService->checkItemOfOwner($params['item_id'], $params['from_id'], $params['from_type']);
	if (!$item) return response(false);

	$item_id = $itemService->separateItem($params['item_id'], $params['quantity']);

	$data = null;
	$data['owner_id'] = $loggedin_user->id;
	$data['type'] = 'user';
	$data['from_id'] = $params['from_id'];
	$data['from_type'] = $params['from_type'];
	$data['to_id'] = $params['to_id'];
	$data['to_type'] = $params['to_type'];
	$data['item_id'] = $item_id;
	$data['message'] = $params['message'];
	return response($giftService->save($data));
});


$app->patch($container['prefix'].'/gift', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$giftService = GiftService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('gift_id', $params)) $params['gift_id'] = 0;

	$gift = $giftService->getGiftByType($params['gift_id'], 'id');
	if (!$gift) return response(false);
	if ($gift->status != 0) return response(false);

	switch ($gift->to_type) {
		case 'user':
			if ($loggedin_user->id != $gift->to_id) return response(false);
			break;
		case 'group':
			$groupService = GroupService::getInstance();
			$group = $groupService->getGroupByType($gift->to_id, 'id');
			if ($group->owner_id != $loggedin_user->id) return response(false);
			break;
		case 'event':
			$eventService = EventService::getInstance();
			$event = $eventService->getEventByType($gift->to_id, 'id');
			if ($event->creator_id != $loggedin_user->id) return response(false);
			break;
		case 'business':
			$businessService = BusinessService::getInstance();
			$business = $businessService->getBusinessByType($gift->to_id, 'id');
			if ($business->owner_id != $loggedin_user->id) return response(false);
			break;
		default:
			return response(false);
			break;
	}

	$itemService->changeOwnerItem($gift->to_id, $gift->to_type, $gift->item_id);

	$gift = object_cast("Gift", $gift);
	$gift->data->status = 1;
	$gift->where = "id = {$gift->id}";
	$gift->update();
	$data = null;
	$data['gift_id'] = $gift->id;
	return response($notificationService->save($data, "gift:accept"));
});

$app->delete($container['prefix'].'/gift', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$giftService = GiftService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('gift_id', $params)) $params['gift_id'] = 0;
	$gift = $giftService->getGiftByType($params['gift_id'], 'id');
	if (!$gift) return response(false);
	if ($gift->status != 0) return response(false);
	
	switch ($gift->to_type) {
		case 'user':
			if ($loggedin_user->id != $gift->to_id) return response(false);
			break;
		case 'group':
			$groupService = GroupService::getInstance();
			$group = $groupService->getGroupByType($gift->to_id, 'id');
			if ($group->owner_id != $loggedin_user->id) return response(false);
			break;
		case 'event':
			$eventService = EventService::getInstance();
			$event = $eventService->getEventByType($gift->to_id, 'id');
			if ($event->creator_id != $loggedin_user->id) return response(false);
			break;
		case 'business':
			$businessService = BusinessService::getInstance();
			$business = $businessService->getBusinessByType($gift->to_id, 'id');
			if ($business->owner_id != $loggedin_user->id) return response(false);
			break;
		default:
			return response(false);
			break;
	}

	$item = $itemService->getItemByType($gift->item_id, 'id');

	$item = object_cast("Item", $item);
	$item->data->status = 1;
	$item->where = "id = {$item->id}";
	$item->update();

	$gift = object_cast("Gift", $gift);
	$gift->data->status = 2;
	$gift->where = "id = {$gift->id}";
	$gift->update();

	$data = null;
	$data['gift_id'] = $gift->id;
	return response($notificationService->save($data, "gift:reject"));
});