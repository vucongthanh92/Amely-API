<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/vouchers', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params))  	$params['offset'] = 0;
	if (!array_key_exists('limit', $params))  	$params['limit'] = 10;

	$product_params = null;
	$product_params[] = [
		'key' => "is_special",
		'value' => "= 1",
		'operation' => ""
	];
	$product_params[] = [
		'key' => "approved",
		'value' => "> 0",
		'operation' => "AND"
	];
	$product_params[] = [
		'key' => "status",
		'value' => "= 1",
		'operation' => "AND"
	];
	$product_params[] = [
		'key' => "time_created",
		'value' => "DESC",
		'operation' => "order_by"
	];

	$products = $productService->getProducts($product_params, $params['offset'], $params['limit']);
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
