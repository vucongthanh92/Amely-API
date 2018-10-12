<?php
use Slim\Http\Request;
use Slim\Http\Response;

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
	$cart = $cartService->getCart($params['cart_id']);
	$cart_items = $cartService->getCartItems($params['cart_id']);
	if ($cart->status == 1) return response(false);
	$order_items_snapshot = [];
	$total = $quantity = 0;
	foreach ($cart_items as $key => $cart_item) {
		$product = $productService->getProductByType($cart_item->product_id, 'id');
		if ($product->snapshot != $cart_item->snapshot_id) return response(false);
		$store_quantity = $productStoreService->checkQuantityInStore($product->id, $cart_item->store_id, $cart_item->quantity);
		if (!$store_quantity) return response(false);
		if ($store_quantity->quantity < $cart_item->quantity) return response(false);
		$quantity += $cart_item->quantity;
		$total += $product->display_price * $cart_item->quantity;
		$order_items_snapshot[] = [
			'product_id' => $product->id,
			'price' => $product->display_price,
			'pdetail_id' => $product->owner_id,
			'snapshot_id' => $product->snapshot,
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
		$cart = object_cast('cart', $cart);
		$cart->data->status = 1;
		$cart->where = "id = {$cart->id}";
		$cart->update();
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