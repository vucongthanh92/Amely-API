<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/orders', function (Request $request, Response $response, array $args) {
	$purchaseOrderService = PurchaseOrderService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$storeService = StoreService::getInstance();
	$shopService = ShopService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('order_id', $params)) return response(false);
	$order = $purchaseOrderService->getPOByType($params['order_id'], 'id');
	if (!$order) return response(false);
	$order_items = unserialize($order->order_items_snapshot);

	if (!$order_items) return response(false);
	$result = $shops_id = $stores_id = $snapshots_id = [];
	foreach ($order_items as $key => $order_item) {
		array_push($stores_id, $order_item['store_id']);
		array_push($snapshots_id, $order_item['snapshot_id']);
	}
	$stores_id = implode(',', array_unique($stores_id));
	$stores = $storeService->getStoresByType($stores_id);

	foreach ($stores as $store) {
		array_push($shops_id, $store->owner_id);
	}
	$shops_id = implode(',', array_unique($shops_id));
	$shops = $shopService->getShopsByType($shops_id);

	$snapshots_id = implode(',', array_unique($snapshots_id));
	$snapshots = $snapshotService->getSnapshotsByType($snapshots_id, 'id');

	$result['total'] = 0;
	foreach ($stores as $store) {
		$total = $tax = 0;
		foreach ($snapshots as $snapshot) {
			foreach ($order_items as $order_item) {
				if ($snapshot->id == $order_item['snapshot_id']) {
					if ($order_item['quantity'] > 0) {
						$snapshot->display_quantity = $order_item['quantity'];
						$snapshot->redeem_quantity = 0;
						$total += $snapshot->display_price * $order_item['quantity'];
						$tax += $snapshot->tax;
						$result['items'][$store->id][] = $snapshot;
					}
					if ($order_item['redeem_quantity'] > 0) {
						$snapshot_redeem = clone $snapshot;
						$snapshot_redeem->display_quantity = 0;
						$snapshot_redeem->redeem_quantity = $order_item['redeem_quantity'];
						$result['items'][$store->id][] = $snapshot_redeem;
					}
				}
			}
		}

		foreach ($shops as $shop) {
			if ($store->owner_id == $shop->id) {
				$store->avatar = $shop->avatar;
			}
		}
		$result['total'] = $result['total'] + $total;
		$store->total = $total;
		$store->tax = $tax;
		$result['stores'][] = $store;
	}

	return response($result);
});

$app->post($container['prefix'].'/orders', function (Request $request, Response $response, array $args) {
	$paymentsService = PaymentsService::getInstance();
	$shippingService = ShippingService::getInstance();

    return response([
		"shipping_methods" => $shippingService->getMethods(),
		"payment_methods" => $paymentsService->findMethodsByCapacity('process')
	]);

});

$app->put($container['prefix'].'/orders', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$paymentsService = PaymentsService::getInstance();
	$productService = ProductService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$productStoreService = ProductStoreService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('payment_fullname', $params))	 $params['payment_fullname'] = $loggedin_user->fullname;
	if (!array_key_exists('payment_phone', $params))	 $params['payment_phone'] = $loggedin_user->mobilelogin;
	if (!array_key_exists('payment_address', $params))	 $params['payment_address'] = $loggedin_user->address;
	if (!array_key_exists('payment_province', $params))	 $params['payment_province'] = $loggedin_user->province;
	if (!array_key_exists('payment_district', $params))	 $params['payment_district'] = $loggedin_user->district;
	if (!array_key_exists('payment_ward', $params))		 $params['payment_ward'] = $loggedin_user->ward;
	if (!array_key_exists('payment_note', $params))		 $params['payment_note'] = false;
	if (!array_key_exists('payment_method', $params))	 $params['payment_method'] = false;
	if (!array_key_exists('shipping_fullname', $params)) $params['shipping_fullname'] = false;
	if (!array_key_exists('shipping_phone', $params))	 $params['shipping_phone'] = false;
	if (!array_key_exists('shipping_address', $params))	 $params['shipping_address'] = false;
	if (!array_key_exists('shipping_province', $params)) $params['shipping_province'] = false;
	if (!array_key_exists('shipping_district', $params)) $params['shipping_district'] = false;
	if (!array_key_exists('shipping_ward', $params))	 $params['shipping_ward'] = false;
	if (!array_key_exists('shipping_note', $params))	 $params['shipping_note'] = false;
	if (!array_key_exists('shipping_method', $params))	 $params['shipping_method'] = false;
	if (!array_key_exists('shipping_fee', $params))		 $params['shipping_fee'] = false;
	if (!array_key_exists('cart_id', $params))		 	 $params['cart_id'] = false;

	if (!$params['payment_method'] || !$params['cart_id']) return response(false);
	$cart = $cartService->getCartByType($params['cart_id'], 'id');
	$cart_items = $cartService->getCartItems($params['cart_id']);
	if ($cart->status == 1) return response(false);

	$order_items_snapshot = [];
	$total = $quantity = 0;
	foreach ($cart_items as $key => $cart_item) {

		$product = $productService->getProductByType($cart_item->product_id, 'id');
		if ($product->snapshot_id != $cart_item->snapshot_id) return response(false);

		$store_quantity = $productStoreService->checkQuantityInStore($product->id, $cart_item->store_id, $cart_item->quantity);

		if (!$store_quantity) return response(false);

		if ($store_quantity->quantity < $cart_item->quantity) return response(false);
		$quantity += $cart_item->quantity;
		$total += $product->display_price * $cart_item->quantity;
		$order_items_snapshot[] = [
			'product_id' => $product->id,
			'price' => $product->display_price,
			'pdetail_id' => $product->owner_id,
			'snapshot_id' => $product->snapshot_id,
			'store_id' => $cart_item->store_id,
			'quantity' => $cart_item->quantity,
			'redeem_quantity' => $cart_item->redeem_quantity
		];
	}


	$po = new PurchaseOrder;
	$po->data->owner_id = $loggedin_user->id;
	$po->data->type = 'user';
	$po->data->payment_method = $params['payment_method'];
	$po->data->shipping_method = $params['shipping_method'];
	$po->data->status = 0;
	$po->data->payment_fullname = $params['payment_fullname'];
	$po->data->payment_phone = $params['payment_phone'];
	$po->data->payment_address = $params['payment_address'];
	$po->data->payment_province = $params['payment_province'];
	$po->data->payment_district = $params['payment_district'];
	$po->data->payment_ward = $params['payment_ward'];
	$po->data->note = $params['payment_note'];
	$po->data->order_items_snapshot = serialize($order_items_snapshot);
	$po->data->total = $total;
	$po->data->quantity = $quantity;
	$po_id = $po->insert(true);
	if ($po_id) {
		$cartService->updateStatus($cart->id, 1);
		$pm = $paymentsService->getMethod($params['payment_method']);
		$pm->order_id = $po_id;
		$pm->amount = $total;
		$pm->creator = $loggedin_user;
		$pm->order_type = "HD";
		$pm->payment_method = $params['payment_method'];
		$url = $pm->process();
		if (!$url) return response(false);
		return response(["url" => $url]);
	}
	return response(false);
});