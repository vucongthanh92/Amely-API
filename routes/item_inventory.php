<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/item_inventory', function (Request $request, Response $response, array $args) {
	$inventoryService = InventoryService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();

	$time = time();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('item_id', $params)) return response(false);
	$item_id = $params['item_id'];
	$item = $itemService->getItemByType($item_id, 'id');
	if (!$item) return response(false);
	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	if (!$snapshot) return response(false);
	
	$store = $storeService->getStoreByType($item->store_id, 'id');
	$snapshot->store = $store;
	$item->snapshot = $snapshot;

	if (($item->stored_end*1 - 259200) <= $time && ($item->stored_end*1) >= $time && $item->stored_end != 0) {
		$item->nearly_stored_expried = true;
	}
	if ($time >= $item->stored_end) {
		$item->stored_expried = true;
	}
	if ($time >= $item->end_day && $item->end_day != 0) {
		$item->used = true;
	}

	$inventory_params = null;
	$inventory_params[] = [
		'key' => 'id',
		'value' => "= {$item->owner_id}",
		'operatation' => ''
	];
	$inventory = $inventoryService->getInventory($inventory_params);
	if (!$inventory) return response(false);
	$obj = new stdClass;
	$obj->id = $inventory->owner_id;
	$obj->type = $inventory->type;

	$item->inventory = $obj;
	return response($item);
});

$app->patch($container['prefix'].'/item_inventory', function (Request $request, Response $response, array $args) {
	$itemService = ItemService::getInstance();
	$walletService = WalletService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$paymentsService = PaymentsService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('payment_method', $params)) return response(false);
	if (!array_key_exists('duration', $params)) return response(false);
	if (!array_key_exists('item_id', $params)) return response(false);
	$item = $itemService->getItemByType($params['item_id']);
	if ($item->status != 1) return response(false);

	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	$total = $snapshot->adjourn_price * $item->quantity * $params['duration'];
	$params['total'] = $total;
	$params['action'] = "RENEW";
	$pm = $paymentsService->getMethod($params['payment_method']);
	$pm->options = $params;
	$pm->order_id = $loggedin_user->id;
	$pm->amount = $total;
	$pm->creator = $loggedin_user;
	$pm->order_type = "WALLET";
	$pm->payment_method = $params['payment_method'];
	$url = $pm->process();
	if (!$url) return response(false);
	return response(["url" => $url]);


});