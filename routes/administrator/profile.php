<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->get($container['administrator'].'/profile', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$storeService = StoreService::getInstance();
	$shopService = ShopService::getInstance();
	$likeService = LikeService::getInstance();
	$relationshipService = RelationshipService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	$loggedin_user = loggedin_user();
	$user_params = null;

	if (array_key_exists("id", $params) && is_numeric($params['id'])) {
		
		$user = $userService->getUserByType($params['id'], 'id');

	} else if (array_key_exists("username", $params) && $params['username'] != null) {

		$user = $userService->getUserByType($params['username'], 'username');

	} else {
		$user = $loggedin_user;
	}
	if (!$user) return response(false);

    if ($user->chain_store) {
    	$store = $storeService->getStoreByType($user->chain_store, 'id');
    	$shop = $shopService->getShopByType($store->owner_id, 'id');
    } else {
    	$shop = $shopService->getShopByType($user->id, 'owner_id');
    }
    if ($shop) {
	    if ($user->chain_store) {
	    	$shop->shop_address = $store->address;
			$shop->shop_phone = $store->phone;
			$shop->shop_province = $store->store_province;
			$shop->shop_district = $store->store_district;
			$shop->shop_ward = $store->store_ward;
			$shop->full_address = $store->full_address;
	    }
	    $user->shop = $shop;
    }

	return response($user);
});