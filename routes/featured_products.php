<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/featured_products', function (Request $request, Response $response, array $args) {
	$advertiseService = AdvertiseService::getInstance();
	$productService = ProductService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$loggedin_user = loggedin_user();
	$advertises = $advertiseService->getAdvertiseProduct();
	if (!$advertises) return response(false);
	$ads = [];

	foreach ($advertises as $key => $advertise) {
		$product = $productService->getProductByType($advertise->target_id, 'id');
		if (!$product) continue;
		if ($product->approved <= 0) continue;
		if ($product->status != 1) continue;
		$store_quantity = $productStoreService->showProduct($product->id);
		if (!$store_quantity) continue;
		if ($store_quantity <= 0) continue;
		$product->advertise_id = $advertise->id;
		array_push($ads, $product);
	}

	return response(array_values($ads));
});