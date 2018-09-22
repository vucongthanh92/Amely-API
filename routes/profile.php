<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->get($container['prefix'].'/profile', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$storeService = StoreService::getInstance();
	$shopService = ShopService::getInstance();
	$likeService = LikeService::getInstance();
	$relationshipService = RelationshipService::getInstance();

	$params = $request->getQueryParams();
	$loggedin_user = loggedin_user();
	$user_params = null;

	if (array_key_exists("guid", $params) && is_numeric($params['guid'])) {
		
		$user = $userService->getUserByType($params['guid'], 'id');

	} else if (array_key_exists("username", $params) && $params['username'] != null) {

		$user = $userService->getUserByType($params['username'], 'username');

	} else {
		$user = $loggedin_user;
	}
	if (!$user) return response(false);

	if (!array_key_exists("type", $params)) $params['type'] = "default";
	switch ($params['type']) {
		case 'notification':
			break;
		default:
			$shop_params = null;
		    if ($user->chain_store) {
		    	$store_params = null;
		    	$store_params[] = [
		    		'key' => 'id',
		    		'value' => "= {$user->chain_store}",
		    		'operation' => ''
		    	];
		    	$store = $storeService->getStore($store_params);
		    	$shop_params[] = [
			    	'key' => 'id',
			    	'value' => "= {$store->owner_guid}",
			    	'operation' => ''
			    ];
		    } else {
			    $shop_params[] = [
			    	'key' => 'owner_guid',
			    	'value' => "= {$user->id}",
			    	'operation' => ''
			    ];
		    }
		    $shop = $shopService->getShop($shop_params);
		    if ($shop) {
			    $is_liked_shop = $likeService->isLiked($loggedin_user->id, $shop->id, 'shop');
			    if ($is_liked_shop) {
			    	$shop->liked = true;
			    }
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
		    if ($loggedin_user->id != $user->id) {
			    $relation = $relationshipService->getFriendRequested($loggedin_user->id, $user->id);
			    if ($relation) {
			        $user->requested = 1;
			    }
		    }
			break;
	}

	return response($user);
});