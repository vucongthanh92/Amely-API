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

	if (!array_key_exists("type", $params)) $params['type'] = "default";
	switch ($params['type']) {
		case 'notification':
			break;
		default:
			$shop_params = null;
			$type = false;
			$input = false;
		    if ($user->chain_store) {
		    	$store = $storeService->getStoreByType($user->chain_store, 'id');
		    	$type = 'id';
		    	$input = $store->owner_id;
		    } else {
		    	$type = 'owner_id';
		    	$input = $user->id;
		    }
		    $shop = $shopService->getShopByType($input, $type);
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
			    if ($shop->approved > 0 && $shop->status == 1) {
			    	$user->shop = $shop;
			    }
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

$app->patch($container['prefix'].'/profile', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('firstname', $params)) 		$params['firstname'] = false;
	if (!array_key_exists('lastname', $params)) 		$params['lastname'] = false;
	if (!array_key_exists('gender', $params)) 			$params['gender'] = false;
	if (!array_key_exists('birthdate', $params)) 		$params['birthdate'] = false;
	if (!array_key_exists('usercurrency', $params)) 	$params['usercurrency'] = "VND";
	if (!array_key_exists('friends_hidden', $params)) 	$params['friends_hidden'] = 0;
	if (!array_key_exists('birthdate_hidden', $params)) $params['birthdate_hidden'] = 0;
	if (!array_key_exists('mobile_hidden', $params)) 	$params['mobile_hidden'] = 0;
	if (!array_key_exists('province', $params)) 		$params['province'] = false;
	if (!array_key_exists('district', $params)) 		$params['district'] = false;
	if (!array_key_exists('ward', $params)) 			$params['ward'] = false;
	if (!array_key_exists('address', $params)) 			$params['address'] = false;

	$user_data['id'] = $loggedin_user->id;
	$user_data['first_name'] = $params['firstname'];
	$user_data['last_name'] = $params['lastname'];
	$user_data['birthdate'] = $params['birthdate'];
	$user_data['gender'] = $params['gender'];
	$user_data['usercurrency'] = $params['usercurrency'];
	$user_data['province'] = $params['province'];
	$user_data['district'] = $params['district'];
	$user_data['ward'] = $params['ward'];
	$user_data['address'] = $params['address'];
	$user_data['friends_hidden'] = $params['friends_hidden'];
	$user_data['birthdate_hidden'] = $params['birthdate_hidden'];
	$user_data['mobile_hidden'] = $params['mobile_hidden'];
	$user_data['language'] = "VN";
	$userService->save($user_data);

	$user = $userService->getUserByType($loggedin_user->id, 'id', true);
	$_SESSION["OSSN_USER"] = $user;

	return response(true);
});