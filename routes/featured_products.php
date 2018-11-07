<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/featured_products', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$productService = ProductService::getInstance();
	$loggedin_user = loggedin_user();
	$advertises = $advertiseService->getAdvertiseProduct();
	if (!$advertises) return response(false);
	$ads = [];
	foreach ($advertises as $key => $advertise) {
		$ads[$advertise->id] = $advertise->target_id;
	}
	$products_id = array_unique(array_values($ads));
	$products_id = implode(',', $products_id);
	if (!$products_id) return response(false);
	$products = $productService->getProductsByType($products_id, 'id');

	foreach ($products as $key => $product) {
		foreach ($ads as $kad => $ad) {
			if ($ad == $product->id) {
				$product->advertise_guid = $ads[$kad];
				$products[$key] = $product;
			}
		}
	}
	return response(array_values($products));
});