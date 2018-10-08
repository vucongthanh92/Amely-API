<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/shops', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;
	if (!$params['shop_id']) return response(false);
	$shop = $shopService->getShopByType($params['shop_id'], 'id');
	if (!$shop) return response(false);
	$shop->liked = false;
	$shops_liked = $shopService->getShopsLiked($loggedin_user->id);
	if ($shops_liked) $shop->liked = true;

	$store_params = null;
	$store_params[] = [
		'key' => 'owner_id',
		'value' => "= {$shop->id}",
		'operation' => ''
	];

	$stores = $storeService->getStores($store_params, 0, 999999999);
	$shop->stores = $stores;

	return response($shop);

});

$app->post($container['prefix'].'/shops', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;

	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];

	$shops_liked = $shopService->getShopsLiked($loggedin_user->id);
	if (!$shops_liked) return response(false);
	$shops_liked = array_map(create_function('$o', 'return $o->subject_id;'), $shops_liked);
	$shops_liked = implode(',', $shops_liked);
	$shops = $shopService->getShopsByType($shops_liked, 'id', $offset, $limit, false);
	if (!$shops) return response(false);
	return response($shops);

});

$app->put($container['prefix'].'/shops', function (Request $request, Response $response, array $args) {
	$shopService = ShopService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) 		$params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('type', $params)) 			$params['type'] = 'user';
	if (!array_key_exists('title', $params)) 			$params['title'] = false;
	if (!array_key_exists('description', $params)) 		$params['description'] = false;
	if (!array_key_exists('shop_bidn', $params)) 		$params['shop_bidn'] = false;
	if (!array_key_exists('friendly_url', $params))		$params['friendly_url'] = false;
	if (!array_key_exists('shipping_method', $params)) 	$params['shipping_method'] = false;
	if (!array_key_exists('owner_name', $params)) 		$params['owner_name'] = $loggedin_user->fullname;
	if (!array_key_exists('owner_phone', $params)) 		$params['owner_phone'] = $loggedin_user->mobilelogin;
	if (!array_key_exists('owner_address', $params)) 	$params['owner_address'] = $loggedin_user->address;
	if (!array_key_exists('owner_province', $params)) 	$params['owner_province'] = $loggedin_user->province;
	if (!array_key_exists('owner_district', $params)) 	$params['owner_district'] = $loggedin_user->district;
	if (!array_key_exists('owner_ward', $params)) 		$params['owner_ward'] = $loggedin_user->ward;
	if (!array_key_exists('owner_ssn', $params)) 		$params['owner_ssn'] = false;
	if (!array_key_exists('status', $params)) 			$params['status'] = 0;
	if (!array_key_exists('files_scan', $params)) 		$params['files_scan'] = false;

	if (!$params['shop_bidn']) return response(false);

	if ($shopService->getShopByType($loggedin_user->id, 'owner_id', false)) return response(false);

	$shop = new Shop();
	$shop->data->owner_id = $params['owner_id'];
	$shop->data->type = $params['type'];
	$shop->data->title = $params['title'];
	$shop->data->description = $params['description'];
	$shop->data->shop_bidn = $params['shop_bidn'];
	$shop->data->friendly_url = $params['friendly_url'];
	$shop->data->shipping_method = $params['shipping_method'];
	$shop->data->owner_name = $params['owner_name'];
	$shop->data->owner_phone = $params['owner_phone'];
	$shop->data->owner_address = $params['owner_address'];
	$shop->data->owner_province = $params['owner_province'];
	$shop->data->owner_district = $params['owner_district'];
	$shop->data->owner_ward = $params['owner_ward'];
	$shop->data->owner_ssn = $params['owner_ssn'];
	$shop->data->status = 0;
	$shop->data->files_scan = $params['files_scan'];
	$shop_id = $shop->insert(true);
	if ($shop_id) {
		$store = new Store();
		$store->data->owner_id = $shop_id;
		$store->data->type = 'shop';
		$store->data->title = $params['title'];
		$store->data->description = $params['description'];
		$store->data->lat = $params['lat'];
		$store->data->lng = $params['lng'];
		$store->data->store_phone = $params['shop_phone'];
		$store->data->store_address = $params['shop_address'];
		$store->data->store_province = $params['shop_province'];
		$store->data->store_district = $params['shop_district'];
		$store->data->store_ward = $params['shop_ward'];
		return response($store->insert());
	}
	return response(false);

});

$app->patch($container['prefix'].'/shops', function (Request $request, Response $response, array $args) {
	
});
