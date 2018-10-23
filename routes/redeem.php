<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/redeem', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$redeemService = RedeemService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) $params['code'] = false;
	if (!$params['code']) return response(false);

	$decrypt = $services->b64decode($params['code']);
	$data = $services->decrypt($decrypt);
	$data = unserialize($data);

	$time = time();
	$time_affter_5m = $data['time'] + (5*60);

	if ($time > $time_affter_5m) return response(false);

	$item = $itemService->getItemByType($data['item_id
		']);

	if ($item->status != 1) return response(false);

	$redeem_parmas = null;
	$redeem_parmas[] = [
		'key' => 'code',
		'value' => "= {$params['code']}",
		'operation' => ''
	];

	$redeem = $redeemService->getRedeem($redeem_parmas);
	if ($redeem) return response(false);

	$item->quantity = $data['quantity'];
	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	$item->snapshot = $snapshot;
	return response($item);
});

$app->post($container['prefix'].'/redeem', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$redeemService = RedeemService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) $params['code'] = false;

	$decrypt = $services->b64decode($params['code']);
	$data = $services->decrypt($decrypt);
	$data = unserialize($data);

	$time = time();
	$time_affter_5m = $data['time'] + (5*60);

	if ($time > $time_affter_5m) return response(false);
	$item_id = $itemService->separateItem($data['item_id'], $data['quantity']);

	$params = null;
	$params['owner_id'] = $data['owner_id'];
	$params['item_id'] = $data['item_id'];
	$params['creator_id'] = $loggedin_user->id;
	$params['code'] = $params['code'];
	$params['status'] = 1;
	$redeem_id = $redeemService->save($params);
	return response(true);

});

$app->put($container['prefix'].'/redeem', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	if (!array_key_exists('quantity', $params)) $params['quantity'] = false;

	$itemService = ItemService::getInstance();
	$item = $itemService->getItemByType($params['item_id'], 'id');
	if (!$item) return response(false);

	switch ($item->type) {
		case 'user':
			if ($item->owner_id != $loggedin_user->id) return response(false);
			break;
		case 'group':
			$groupService = GroupService::getInstance();
			$group = $groupService->getGroupByType($item->owner_id, 'id');
			if ($group->owner_id != $loggedin_user->id) return response(false);
			break;
		case 'event':
			$eventService = EventService::getInstance();
			$event = $eventService->getEventByType($item->owner_id, 'id');
			if ($event->creator_id != $loggedin_user->id) return response(false);
			break;
		case 'business':
			$businessService = Business::getInstance();
			$business = $businessService->getBusinessByType($item->owner_id, 'id');
			if ($business->owner_id != $loggedin_user->id) return response(false);
			break;
		default:
			return response(false);
			break;
	}
	$data = [];
	$data['owner_id'] = $loggedin_user->id;
	$data['item_id'] = $params['item_id'];
	$data['quantity'] = $params['quantity'];
	$data['time'] = time();
	$encrypt = $services->encrypt(serialize($data));
	$code = $services->b64encode($encrypt);

	return response($code);
});