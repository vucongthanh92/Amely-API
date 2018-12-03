<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin he thong
$app->post($container['administrator'].'/product_store', function (Request $request, Response $response, array $args) {
	$productStoreService = ProductStoreService::getInstance();
	$storeService = StoreService::getInstance();
	
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('product_id', $params)) 	return response(false);
	if (!array_key_exists('store_id', $params)) return response(false);
	if (!array_key_exists('quantity', $params)) return response(false);

	$store = $storeService->getStoreByType($params['store_id'], 'id', false);
	if (!$store) return response(false);
	$product_store_data = [];
	$product_store_data['store_id'] = $params['store_id'];
	$product_store_data['product_id'] = $params['product_id'];
	$product_store_data['quantity'] = $params['quantity'];
	$product_store_data['owner_id'] = $store->owner_id;
	$product_store_data['creator_id'] = $loggedin_user->id;
	return response($productStoreService->save($product_store_data));
});