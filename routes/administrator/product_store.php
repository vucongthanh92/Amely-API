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

$app->put($container['administrator'].'/product_store', function (Request $request, Response $response, array $args) {
	$productStoreService = ProductStoreService::getInstance();
	$storeService = StoreService::getInstance();
	$shopService = ShopService::getInstance();
	$productService = ProductService::getInstance();
	
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) 	return response(false);
	if (!array_key_exists('offset', $params)) 	$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 	$params['limit'] = 10;

	$shop = $shopService->getShopByType($params['shop_id'], 'id');
	$stores = $storeService->getStoresByType($shop->id, 'owner_id', false);
	$products_id = $stores_id = [];
	foreach ($stores as $key => $store) {
		array_push($stores_id, $store->id);
	}
	$stores_id = array_unique($stores_id);
	$stores_id = implode(',', $stores_id);

	$result['stores'] = $stores;

	$ps_params = null;
	$ps_params[] = [
		'key' => 'store_id',
		'value' => "IN ($stores_id)",
		'operation' => ''
	];

	$ps = $productStoreService->getQuantityProducts($ps_params, $params['offset'], $params['limit']);

	foreach ($ps as $key => $value) {
		array_push($products_id, $value->product_id);
		$result['items'][] = [
			'store_id' => $value->store_id,
			'product_id' => $value->product_id,
			'quantity' => $value->quantity
		];
	}
	$products_id = array_unique($products_id);
	$products_id = implode(',', $products_id);

	$products = $productService->getProductsByType($products_id, 'id');
	$result['products'] = $products;

	return response($result);


});