<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/item_inventory', function (Request $request, Response $response, array $args) {
	$snapshotProductService = SnapshotProductService::getInstance();
	$snapshotProductDetailService = SnapshotProductDetailService::getInstance();
	$inventoryService = InventoryService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$time = time();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('item_id', $params)) return response(false);
	$item_id = $params['item_id'];
	$item = $inventoryService->getItemByType($item_id, 'id');
	if (!$item) return response(false);
	$snapshot_product = $snapshotProductService->getSnapshotByType($item->product_snapshot, 'id');
	if (!$snapshot_product) return response(false);
	$snapshot_pdetail = $snapshotProductDetailService->getSnapshotByType($snapshot_product->owner_id, 'id');
	$product_snapshot = (object) array_merge((array) $snapshot_product, (array) $snapshot_pdetail);
	$store = $storeService->getStoreByType($item->store_id, 'id');
	$product_snapshot->store = $store;
	$item->product_snapshot = $product_snapshot;
	$item->adjourn_price = $snapshot_pdetail->adjourn_price;

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