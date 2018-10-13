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
	$item->snapshot_id = $snapshot;
	$item->adjourn_price = $snapshot->adjourn_price;

	if (($item->stored_end*1 - 259200) <= $time && ($item->stored_end*1) >= $time && $item->stored_end != 0) {
		$item->nearly_stored_expried = true;
	}
	if ($time >= $item->stored_end) {
		$item->stored_expried = true;
	}
	if ($time >= $item->end_day && $item->end_day != 0) {
		$item->used = true;
	}

	return response($item);

});