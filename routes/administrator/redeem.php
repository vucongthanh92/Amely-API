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

	$loggedin_user = loggedin_user();

	if (!$loggedin_user->chain_store) return response(false);
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('code', $params)) $params['code'] = false;
	$redeem_parmas = null;
	$redeem_parmas[] = [
		'key' => 'code',
		'value' => "= '{$params['code']}'",
		'operation' => ''
	];
	$redeem = $redeemService->getRedeem($redeem_parmas);
	if ($redeem) return response(false);
	$decrypt = $services->b64decode($params['code']);
	$data = $services->decrypt($decrypt);
	$data = unserialize($data);

	$time = time();
	$time_affter_5m = $data['time'] + (5*60);

	if ($time > $time_affter_5m) return response(false);
	$item_id = $itemService->separateItem($data['item_id'], $data['quantity']);

	$redeem_data = null;
	$redeem_data['owner_id'] = $data['owner_id'];
	$redeem_data['item_id'] = $item_id;
	$redeem_data['creator_id'] = $loggedin_user->id;
	$redeem_data['code'] = $params['code'];
	$redeem_data['store_id'] = $loggedin_user->chain_store;
	$redeem_data['status'] = 1;
	$redeem_id = $redeemService->save($redeem_data);

	$transaction_params = $transactionService->getTransactionParams($data['owner_id'], 'user', '', '', 'redeem', $redeem_id, 14, $data['owner_id']);
    $transactionService->save($transaction_params);

    $transaction_params = $transactionService->getTransactionParams($loggedin_user->chain_store, 'store', '', '', 'redeem', $redeem_id, 14, $loggedin_user->id);
    $transactionService->save($transaction_params);
	return response(true);
});