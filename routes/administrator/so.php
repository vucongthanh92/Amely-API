<?php
use Slim\Http\Request;
use Slim\Http\Response;

// xem chi tiet don hang
$app->get($container['administrator'].'/so', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();
	$deliveryOrderService = DeliveryOrderService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$userService = UserService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('so_id', $params)) return responseError(ERROR_0);

	$so = $supplyOrderService->getSOByType($params['so_id'], 'id');
	$po = $purchaseOrderService->getPOByType($so->owner_id, 'id');
	$dos = $deliveryOrderService->getDOsBySO($so_id, 0, 999999, true);

	$customer = $userService->getUserByType($po->owner_id, 'id', true);

	$order_items = unserialize($so->order_items_snapshot);
	if (!$order_items) return response(false);
	$result = $snapshots_id = [];
	foreach ($order_items as $key => $order_item) {
		array_push($snapshots_id, $order_item['snapshot_id']);
	}
	$total = $tax = 0;
	$snapshots_id = implode(',', array_unique($snapshots_id));
	$snapshots = $snapshotService->getSnapshotsByType($snapshots_id, 'id');
	foreach ($snapshots as $ksnapshot => $snapshot) {
		foreach ($order_items as $order_item) {
			if ($snapshot->id == $order_item['snapshot_id']) {
				$snapshot->display_quantity = $order_item['quantity'];
				$snapshot->redeem_quantity = $order_item['redeem_quantity'];
				$total += $snapshot->display_price * $order_item['quantity'];
				$tax += $snapshot->tax;
			}
		}
		$result['items'][] = $snapshot;
	}
	$so->items = $snapshots;
	if ($dos) {
		
		$result['dos'] = $dos;
	}
	$result['po'] = $po;
	$result['so'] = $so;
	$result['customer'] = $customer;

	return response($result);
});

// lay danh sach don hang
$app->put($container['administrator'].'/so', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	/* shop_id 
		false la lay theo store_id
	*/
	if (!array_key_exists('shop_id', $params))  $params['shop_id'] = false;
	/* store_id
		
	*/

	if (!array_key_exists('store_id', $params))  $params['store_id'] = false;
	/* status
		-1 lay tat ca
		0 la don hang dang cho xu ly
		1 la don hang thanh cong
	*/
	if (!array_key_exists('status', $params))  	$params['status'] = -1;
	if (!array_key_exists('offset', $params))  	$params['offset'] = 0;
	if (!array_key_exists('limit', $params))  	$params['limit'] = 10;

	if (!$params['shop_id'] && !$params['store_id']) return response(false);

	$so_params = null;
	if ($params['store_id']) {
		$stores_id = $params['store_id'];
	}
	if ($params['shop_id']) {
		$shop = $shopService->getShopByType($params['shop_id'], 'id');
		$stores = $storeService->getStoresByShop($shop->id, false);
		$stores_id = implode(',', array_map(create_function('$o', 'return $o->id;'), $stores));
	}
	$so_params[] = [
		'key' => 'store_id',
		'value' => "IN ({$stores_id})",
		'operation' => ''
	];
	if ($params['status'] >= 0) {
		$so_params[] = [
			'key' => 'status',
			'value' => "= {$params['status']}",
			'operation' => 'AND'
		];	
	}
	$sos = $supplyOrderService->getSOs($so_params, $params['offset'], $params['limit']);

	return response($sos);
});
