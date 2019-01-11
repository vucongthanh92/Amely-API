<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['administrator'].'/do', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();
	$deliveryOrderService = DeliveryOrderService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$userService = UserService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('do_id', $params)) return responseError(ERROR_0);


	$do = $deliveryOrderService->getDOByType($params['do_id'], 'id');
	if (!$do) return response(false);
	$so = $supplyOrderService->getSOByType($do->so_id, 'id');
	if (!$so) return response(false);
	$do->so = $so;
	$po = $purchaseOrderService->getPOByType($so->owner_id, 'id');
	if (!$po) return response(false);
	$do->po = $po;
	$store = $storeService->getStoreByType($do->store_id, 'id', true);
	if (!$store) return response(false);
	$shop = $shopService->getShopByType($store->owner_id, 'id');
	if (!$shop) return response(false);
	$shop->store = $store;
	$do->shop = $shop;
	$owner = $userService->getUserByType($do->owner_id, 'id');
	$do->owner = $owner;

	$items = [];
	if ($do->item_id) {
		$item = $itemService->getItemByType($do->item_id, 'id');
		$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
		$snapshot->display_quantity = $item->quantity;
		$items[] = $snapshot;
	} else {
		$order_items = unserialize($do->order_items_snapshot);
		foreach ($order_items as $key => $order_item) {
			$snapshot = $snapshotService->getSnapshotByType($order_item['snapshot_id'], 'id');
			$snapshot->display_quantity = $order_item['quantity'];
			$snapshot->redeem_quantity = $order_item['redeem_quantity'];
			$items[] = $snapshot;
		}
	}
	if (!$items) return response(false);
	$do->items = $items;


	return response($do);
});

$app->put($container['administrator'].'/do', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$supplyOrderService = SupplyOrderService::getInstance();
	$deliveryOrderService = DeliveryOrderService::getInstance();

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

	// if (!$params['shop_id'] && !$params['store_id']) return response(false);

	$so_params = null;
	$check = '';
	if ($params['store_id']) {
		$stores_id = $params['store_id'];
	}
	if ($params['shop_id']) {
		$shop = $shopService->getShopByType($params['shop_id'], 'id');
		$stores = $storeService->getStoresByShop($shop->id, false);
		$stores_id = implode(',', array_map(create_function('$o', 'return $o->id;'), $stores));
	}

	if ($stores_id) {
		$do_params[] = [
			'key' => 'store_id',
			'value' => "IN ({$stores_id})",
			'operation' => ''
		];
		$check = 'AND';
	}

	if ($params['status'] >= 0) {
		$do_params[] = [
			'key' => 'status',
			'value' => "= {$params['status']}",
			'operation' => $check
		];
	}

	$dos = $deliveryOrderService->getDos($do_params, $params['offset'], $params['limit']);

	return response($dos);
});