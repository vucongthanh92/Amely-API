<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/shop/featured_products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$loggedin_user = loggedin_user();
    $params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("shop_id", $params)) 		$params["shop_id"] = false;
	if (!$params['shop_id']) return response(false);

	$product_params = null;
	$product_params[] = [
		'key' => 'owner_id',
		'value' => "= {$params['shop_id']}",
		'operation' => ''
	];
	$product_params[] = [
		'key' => 'approved',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'status',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'featured',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'product_order',
		'value' => 'DESC',
		'operation' => 'order_by'
	];

	$products = $productService->getProducts($product_params, 0, 16);
	if (!$products) return response(false);
	foreach ($products as $key => $product) {
		$store_quantity = ProductStoreService::getInstance()->showProduct($product->id);
		if (!$store_quantity) {
			unset($products[$key]);
			continue;
		}
	}
	if (!$products) return response(false);
	return response(array_values($products));
});