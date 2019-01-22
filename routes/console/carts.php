<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/console_carts', function (Request $request, Response $response, array $args) {
	$productStoreService = ProductStoreService::getInstance();
	$cartService = CartService::getInstance();
	$time = time();

	$time_check = strtotime("-1 day", $time);

	$cart_params = null;
	$cart_params[] = [
		'key' => 'time_created',
		'value' => "< {$time_check}",
		'operation' => ''
	];
	$cart_params[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => 'AND'
	];

	$carts = $cartService->getCarts($cart_params, 0, 999999999);
	if ($carts) {
		foreach ($carts as $key => $cart) {
			$cart_items = $cartService->getCartItems($cart->id);
			if ($cart_items) {
				foreach ($cart_items as $key => $cart_item) {
					$productStoreService->updateQuantity($cart_item->product_id, $cart_item->store_id, -$cart_item->quantity);
				}
			}
			$cartService->updateStatus($cart->id, 1);
		}
	}
	return response(true);


})->setName('console_carts');
