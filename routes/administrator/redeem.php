<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['administrator'].'/redeem', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$userService = UserService::getInstance();
	$redeemService = RedeemService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('redeem_id', $params)) $params['redeem_id'] = false;

	if ($params['redeem_id']) {
		$redeem = $redeemService->getRedeemByType($params['redeem_id'], 'id');
		$item = $itemService->getItemByType($redeem->item_id, 'id');
		$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
		$item->snapshot = $snapshot;
		$shop = $shopService->getShopByType($snapshot->owner_id, 'id');
		$store = $storeService->getStoreByType($redeem->store_id, 'id', true);
		$shop->store = $store;
		$user = $userService->getUserByType($redeem->creator_id, 'id', false);
		return response([
			'item' => $item,
			'shop' => $shop,
			'user' => $user
		]);
	}

	return response(false);
});

$app->put($container['administrator'].'/redeem', function (Request $request, Response $response, array $args) {
	$transactionService = TransactionService::getInstance();
	$services = Services::getInstance();
	$redeemService = RedeemService::getInstance();
	$itemService = ItemService::getInstance();
	$storeService = StoreService::getInstance();

	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;
	if (!array_key_exists('store_id', $params)) $params['store_id'] = false;

	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;

	$stores_id = false;
	$redeem_parmas = null;
	$redeem_parmas[] = [
		'key' => 'time_created',
		'value' => "DESC",
		'operation' => 'order_by'
	];
	$redeem_parmas[] = [
		'key' => 'time_created',
		'value' => "> 0",
		'operation' => ''
	];
	if ($params['shop_id']) {
		$stores = $storeService->getStoresByShop($params['shop_id'], false);
		if ($stores) {
			$stores_id = array_unique(array_map(create_function('$o', 'return $o->id;'), $stores));
			$stores_id = implode(',', $stores_id);
		}
	}
	if ($params['store_id']) {
		$stores_id = $params['store_id'];
	}
	if ($stores_id) {
		$redeem_parmas[] = [
			'key' => 'store_id',
			'value' => "IN ({$stores_id})",
			'operation' => 'AND'
		];
	}
	$redeems = $redeemService->getRedeems($redeem_parmas, $params['offset'], $params['limit']);
	if (!$redeems) return response(false);
	foreach ($redeems as $key => $redeem) {
		$store = $storeService->getStoreByType($redeem->store_id, 'id', false);
		$redeem->store = $store;
	}
	return response($redeems);
});