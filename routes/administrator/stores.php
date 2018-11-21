<?php
use Slim\Http\Request;
use Slim\Http\Response;

// them hoac chinh sua cua hang
$app->post($container['administrator'].'/stores', function (Request $request, Response $response, array $args) {
	$storeService = StoreService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) 				$params['id'] = false;
	if (!array_key_exists('owner_id', $params)) 		return response(false);
	if (!array_key_exists('title', $params)) 			$params['title'] = 0;
	if (!array_key_exists('description', $params)) 		$params['description'] = 0;
	if (!array_key_exists('lat', $params)) 				$params['lat'] = 0;
	if (!array_key_exists('lng', $params)) 				$params['lng'] = 0;
	if (!array_key_exists('store_phone', $params)) 		$params['store_phone'] = 0;
	if (!array_key_exists('store_address', $params)) 	$params['store_address'] = 0;
	if (!array_key_exists('store_province', $params)) 	$params['store_province'] = 0;
	if (!array_key_exists('store_district', $params)) 	$params['store_district'] = 0;
	if (!array_key_exists('store_ward', $params)) 		$params['store_ward'] = 0;
	if (!array_key_exists('status', $params)) 			$params['status'] = 0;

	$store_data = null;
	if ($params['id']) {
		$store_data['id'] = $params['id'];	
	}
	$store_data['owner_id'] = $params['owner_id'];
	$store_data['title'] = $params['title'];
	$store_data['description'] = $params['description'];
	$store_data['lat'] = $params['lat'];
	$store_data['lng'] = $params['lng'];
	$store_data['store_phone'] = $params['store_phone'];
	$store_data['store_address'] = $params['store_address'];
	$store_data['store_province'] = $params['store_province'];
	$store_data['store_district'] = $params['store_district'];
	$store_data['store_ward'] = $params['store_ward'];
	$store_data['status'] = $params['status'];
	
	return response($storeService->save($store_data));
});

// xoa chi nhanh
$app->delete($container['administrator'].'/stores', function (Request $request, Response $response, array $args) {
	$storeService = StoreService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) return response(false);

	$store = $storeService->getStoreByType($params['id'], 'id', false);

	if ($loggedin_user->type == 'admin') {
		return response($storeService->delete($store->id));
	}

	if ($loggedin_user->shop->id == $store->owner_id) {
		return response($storeService->delete($store->id));
	}

	return response(false);
});

