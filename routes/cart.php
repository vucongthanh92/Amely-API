<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productService = ProductDetailService::getInstance();
	$subProductDetailService = SubProductDetailService::getInstance();
	$tax = $total = 0;
	$params = $request->getParsedBody();
    	
	$cartService->clearCart();

	if (!$params) $params = [];
	if (!array_key_exists('items', $params))  	$params['items'] = false;

	if (!$params['items']) return response(false);
	$items = $params['items'];
	$products_id = $subproducts_id = [];
	foreach ($items as $key => $item) {
		if (!in_array($item['id'], $subproducts_id)) {
			array_push($subproducts_id, $item['id']);
		}
	}
	if (!$subproducts_id) return response(false);
	$subproducts_id = implode(',', $subproducts_id);
	$subproducts = $subProductDetailService->getSubProductsByType($subproducts_id, 'id');
	if (!$subproducts) return response(false);

	$cart = [];
	foreach ($subproducts as $key => $subproduct) {
		if (!in_array($subproduct->owner_id, $products_id)) {
			array_push($products_id, $subproduct->owner_id);
		}
		foreach ($items as $key => $item) {
			if ($subproduct->current_sub_snapshot != $item['snapshot']) return response(false);
			if ($subproduct->quantity < $item['quantity']) return response(false);
			if (!in_array($item['id'], $subproducts_id)) {
				array_push($subproducts_id, $item['id']);
			}
			if ($item['id'] == $subproduct->id) {
				$subproduct->display_quantity = $item['quantity'];
			}
		}

		$price = $subProductDetailService->getPrice($subproduct);
		$total += $price;
		$subproduct->redeem_quantity = $item['redeem_quantity'];
		$subproduct->store = $item['store'];
		$cartService->saveItems($subproduct);
	}

	if (!$products_id) return response(false);
	$products_id = implode(',', $products_id);
	$products = $productService->getProductsByType($products_id, 'id');
	if (!$products) return response(false);

	foreach ($products as $key => $product) {
		$tax += $product->tax;
	}
	$cartService->saveTotal($total);
	$cartService->saveTax($tax);

	return response($cartService->getCart());

});

$app->put($container['prefix'].'/cart', function (Request $request, Response $response, array $args) {
	$cartService = CartService::getInstance();
	$productService = ProductDetailService::getInstance();
	$subProductDetailService = SubProductDetailService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('items', $params))  	$params['items'] = false;

});