<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/gift', function (Request $request, Response $response, array $args) {
	$giftService = GiftService::getInstance();
	$userService = UserService::getInstance();
	$groupService = GroupService::getInstance();
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
			$from_owner = $userService->getUserByType($from_id, 'id');
			break;
		case 'group':
			$from_owner = $groupService->getGroupByType($from_id);
			break;
		case 'event':
			# code...
			break;
		case 'business':
			# code...
			break;
		default:
			# code...
			break;
	}

	switch ($to_type) {
		case 'user':
			$to_owner = $userService->getUserByType($to_id, 'id');
			break;
		case 'group':
			$to_owner = $groupService->getGroupByType($to_id);
			break;
		case 'event':
			# code...
			break;
		case 'business':
			# code...
			break;
		default:
			# code...
			break;
	}
	$gift->from_owner = $from_owner;
	$gift->to_owner = $to_owner;
	$item = $itemService->getItemByType($gift->item_id);
	if (!$item) return response(false);
	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	$item->snapshot = $snapshot;
	$gift->item = $item;
	return response($gift);
});

$app->post($container['prefix'].'/gift', function (Request $request, Response $response, array $args) {
	$giftService = GiftService::getInstance();
	$itemService = itemService::getInstance();
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
	if (!array_key_exists('status', $params)) $params['status'] = 0;

	if (!$params['time_created'] || !$params['from_id'] || !$params['from_type'] || !$params['to_id'] || !$params['to_type'] || !$params['item_id'] || !$params['status'] || $params['quantity']) return response(false);

	$item = $itemService->getItemByType($params['item_id'], 'id');
	if ($item->owner_id != $params['from_id']) return response(false);

	$item_id = $itemService->separateItem($params['item_id'], $params['quantity']);

	$data = null;
	$data['owner_id'] = $loggedin_user->id;
	$data['type'] = 'user';
	$data['from_id'] = $params['from_id'];
	$data['from_type'] = $params['from_type'];
	$data['to_id'] = $params['to_id'];
	$data['to_type'] = $params['to_type'];
	$data['item_id'] = $item_id;

	return response($giftService->save($data));
});