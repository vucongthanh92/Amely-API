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
	$loggedin_user = loggedin_user();
	$cartService = CartService::getInstance();
	$paymentsService = PaymentsService::getInstance();
	$subProductDetailService = SubProductDetailService::getInstance();
	$snapshotService = SnapshotService::getInstance();

	$cart_items = $cartService->getCartItems($cart->id);
	// $pm = $paymentsService->getMethod('onepay/opcreditcard');

	if (!array_key_exists('payment_fullname', $params))	 $params['payment_fullname'] = false;
	if (!array_key_exists('payment_phone', $params))	 $params['payment_phone'] = false;
	if (!array_key_exists('payment_address', $params))	 $params['payment_address'] = false;
	if (!array_key_exists('payment_province', $params))	 $params['payment_province'] = false;
	if (!array_key_exists('payment_district', $params))	 $params['payment_district'] = false;
	if (!array_key_exists('payment_ward', $params))		 $params['payment_ward'] = false;
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

	if (!$params['payment_method']) return response(false);

	$order_item_snapshot = [];
	foreach ($cart_items as $key => $cart_item) {
		$
		$sp = $subProductDetailService->getSubProductByType($item->id, 'id');
		if ($sp->current_sub_snapshot != $item->current_sub_snapshot) return response(false);
		if ($sp->quantity < $item->display_quantity) return response(false);
		$order_item_snapshot[] = [
			'id' => $sp->id,
			'snapshot' => $item->current_sub_snapshot,
			'store' => $item->store,
			'quantity' => $item->display_quantity,
			'redeem_quantity' => $item->redeem_quantity
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
	$po->data->order_item_snapshot = serialize($order_item_snapshot);
	$po_id = $po->insert(true);
	if ($po_id) {
		
	}

	die('1234');
});