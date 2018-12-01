<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 cua hang co nhieu chi nhanh 
$app->get($container['administrator'].'/shops', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) return response(false);

	$shop = $shopService->getShopByType($params['shop_id'], 'id');
	if (!$shop) return response(false);

	$store_params = null;
	$store_params[] = [
		'key' => 'owner_id',
		'value' => "= {$shop->id}",
		'operation' => ''
	];
	$store_params[] = [
		'key' => 'status',
		'value' => "IN (0,1)",
		'operation' => 'AND'
	];

	$stores = $storeService->getStores($store_params, 0, 999999999);
	$shop->stores = $stores;

	return response($shop);
});

// them hoac chinh sua cua hang
$app->post($container['administrator'].'/shops', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) 				$params['id'] = false;
	if (!array_key_exists('owner_id', $params)) 		$params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('title', $params)) 			$params['title'] = false;
	if (!array_key_exists('description', $params)) 		$params['description'] = false;
	if (!array_key_exists('shop_bidn', $params)) 		$params['shop_bidn'] = false;
	if (!array_key_exists('friendly_url', $params))		$params['friendly_url'] = false;
	if (!array_key_exists('shipping_method', $params)) 	$params['shipping_method'] = false;
	if (!array_key_exists('owner_ssn', $params)) 		$params['owner_ssn'] = false;
	if (!array_key_exists('status', $params)) 			$params['status'] = 0;
	// thong tin chi nhanh
	if (!array_key_exists('lat', $params)) 				$params['lat'] = 0;
	if (!array_key_exists('lng', $params)) 				$params['lng'] = 0;
	if (!array_key_exists('store_phone', $params)) 		$params['store_phone'] = 0;
	if (!array_key_exists('store_address', $params)) 	$params['store_address'] = 0;
	if (!array_key_exists('store_province', $params)) 	$params['store_province'] = 0;
	if (!array_key_exists('store_district', $params)) 	$params['store_district'] = 0;
	if (!array_key_exists('store_ward', $params)) 		$params['store_ward'] = 0;

	if (!$params['id']) {
		$shop = $shopService->getShopByType($params['owner_id'], 'owner_id');
		if ($shop) return response(false);
	}


	if ($params['owner_id'] == $loggedin_user->id) {
		$user = $loggedin_user;
	} else {
		$userService = UserService::getInstance();
		$user = $userService->getUserByType($params['owner_id'], 'id', true);
	}
	$params['owner_name'] = $user->fullname;
	$params['owner_phone'] = $user->mobilelogin;
	$params['owner_address'] = $user->address;
	$params['owner_province'] = $user->province;
	$params['owner_district'] = $user->district;
	$params['owner_ward'] = $user->ward;

	$shop_data = null;
	if ($params['id']) {
		$shop_data['id'] = $params['id'];	
	}
	$shop_data['owner_id'] = $params['owner_id'];
	$shop_data['title'] = $params['title'];
	$shop_data['description'] = $params['description'];
	$shop_data['shop_bidn'] = $params['shop_bidn'];
	$shop_data['friendly_url'] = $params['friendly_url'];
	$shop_data['shipping_method'] = $params['shipping_method'];
	$shop_data['owner_name'] = $params['owner_name'];
	$shop_data['owner_phone'] = $params['owner_phone'];
	$shop_data['owner_address'] = $params['owner_address'];
	$shop_data['owner_province'] = $params['owner_province'];
	$shop_data['owner_district'] = $params['owner_district'];
	$shop_data['owner_ward'] = $params['owner_ward'];
	$shop_data['owner_ssn'] = $params['owner_ssn'];
	$shop_data['status'] = $params['status'];
	$shop_data['introduce'] = $params['introduce'];
	$shop_data['policy'] = $params['policy'];
	$shop_data['contact'] = $params['contact'];

	$files = $request->getUploadedFiles();
    $images = false;
    if ($files) {
		$images['avatar'] = $files['avatar'];
		$images['cover'] = $files['cover'];
		$images['files_scan'] = $files['files_scan'];
    }
	
	$shop_id = $shopService->save($shop_data, $images);
	if ($shop_id) {
		$storeService = StoreService::getInstance();
		$store_data = null;
		$store_data['owner_id'] = $shop_id;
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
		$storeService->save($store_data);
		return response(true);
	}

	return response(false);
});

// thong tin nhieu cua hang
$app->put($container['administrator'].'/shops', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;
	if (!array_key_exists("friends", $params)) $params["friends"] = false;

	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];

	$shop_params[] = [
		'key' => 'status',
		'value' => "IN (0,1)",
		'operation' => ''
	];

	$shops = $shopService->getShops($shop_params, $offset, $limit, false);
	if (!$shops) return response(false);
	return response($shops);
});

// xoa cua hang
$app->delete($container['administrator'].'/shops', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) return response(false);

	$shop = $shopService->getShopByType($params['id'], 'id');

	$product = $productService->getProductByType($params['id'], 'owner_id');
	if ($product) {
		return response([
			'status' => false,
			'error' => "product_exist"
		]);
	}

	if ($loggedin_user->type == 'admin') {
		return response($shopService->delete($shop->id));
	}

	return response(false);
});

