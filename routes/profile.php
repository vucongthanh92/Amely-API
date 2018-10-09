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

$app->path($container['prefix'].'/profile', function (Request $request, Response $response, array $args) {

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('firstname', $params) 		$params['firstname'] = false;
	if (!array_key_exists('lastname', $params) 			$params['lastname'] = false;
	if (!array_key_exists('gender', $params) 			$params['gender'] = false;
	if (!array_key_exists('birthdate', $params) 		$params['birthdate'] = false;
	if (!array_key_exists('usercurrency', $params) 		$params['usercurrency'] = "VND";
	if (!array_key_exists('friends_hidden', $params) 	$params['friends_hidden'] = 0;
	if (!array_key_exists('birthdate_hidden', $params) 	$params['birthdate_hidden'] = 0;
	if (!array_key_exists('mobile_hidden', $params) 	$params['mobile_hidden'] = 0;
	if (!array_key_exists('province', $params) 			$params['province'] = false;
	if (!array_key_exists('district', $params) 			$params['district'] = false;
	if (!array_key_exists('ward', $params) 				$params['ward'] = false;
	if (!array_key_exists('address', $params) 			$params['address'] = false;

	$user = new User();
	$user->id = $loggedin_user->id;
	$user->data->first_name = $params['firstname'];
	$user->data->last_name = $params['lastname'];
	$user->data->gender = $params['gender'];
	$user->data->birthdate = $params['birthdate'];
	$user->data->usercurrency = $params['usercurrency'];
	$user->data->friends_hidden = $params['friends_hidden'];
	$user->data->birthdate_hidden = $params['birthdate_hidden'];
	$user->data->mobile_hidden = $params['mobile_hidden'];
	$user->data->province = $params['province'];
	$user->data->district = $params['district'];
	$user->data->ward = $params['ward'];
	$user->data->address = $params['address'];

	return response($user->update());
});