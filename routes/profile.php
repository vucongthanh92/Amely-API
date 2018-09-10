<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->get('/profile', function (Request $request, Response $response, array $args) {

	// $likes = new OssnLikes;
	// $user = null;
	$params = $request->getQueryParams();
	$loggedin_user = loggedin_user();
	$user_params = null;

	if ($params['guid'] && is_numeric($params['guid'])) {
		$user_params[] = [
			'key' => 'guid',
			'value' => "= {$params['guid']}",
			'operation' => ''
		];
		$user = getUsers($user_params,0,1);
	} else if ($params['username'] && $params['username'] != null) {
		$user_params[] = [
			'key' => 'username',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
		$user = getUsers($user_params,0,1);
	} else {
		$user = $loggedin_user;
	}

	
	var_dump($user);die();


	// $shop = ShopsService::getInstance()->getShopOnView($shop_params,0,1);


	// $conditions = null;
	// $conditions[] = [
	// 	'key' => 'guid',
	// 	'value' => '',
	// 	'operation' => 'query_params'
	// ];
	// $conditions[] = [
	// 	'key' => 'guid',
	// 	'value' => "= {$loggedin_user->guid}",
	// 	'operation' => ''
	// ];
	// $conditions[] = [
	// 	'key' => 'type',
	// 	'value' => "= 'shop'",
	// 	'operation' => 'AND'
	// ];
	// $likes = getLikes($conditions,);

	// $shops_liked = (array)$likes->GetSubjectsLikes($loggedin_user->guid, "shop");
	// $shops_liked_guid = [];
	// if (is_array($shops_liked) && count($shops_liked) > 0) {
	// 	foreach ($shops_liked as $shop) {
	// 		$shops_liked_guid[] = $shop->subject_id;
	// 	}
	// }
	// $user_params = null;

	// if (input('guid')) {
	// 	$guid = input('guid');
	// 	$user_params[] = [
	// 		'key' => 'guid',
	// 		'value' => "= {$guid}",
	// 		'operation' => ''
	// 	];
	// } else if (input('username')) {
	// 	$username = input('username');
	// 	$user_params[] = [
	// 		'key' => 'username',
	// 		'value' => "= '{$username}'",
	// 		'operation' => ''
	// 	];
	// } else {
	// 	$user_params[] = [
	// 		'key' => 'guid',
	// 		'value' => "= {$loggedin_user->guid}",
	// 		'operation' => ''
	// 	];
	// }
	// $user = (new OssnUser)->getUsersOnView($user_params,0,1);
	// if (!$user) return false;

	// if ($user->guid != $loggedin_user->guid) {
	// 	$block_list = json_decode($loggedin_user->blockedusers);
	//     if (is_array($block_list) && count($block_list) > 0) {
	// 	    if (in_array($user->guid, $block_list)) {
	// 	    	return [
	// 				"error"   => "User is blocked",
	// 				"status"  => false
	// 			];
	// 	    }
	//     }
	// }

	// if (ossn_relation_exists($loggedin_user->guid, $user->guid, "friend:request")) {
 //        $user->requested = 1;
 //    }

 //    $shop_params = null;
 //    if ($user->chain_store) {
 //    	$store_params = null;
 //    	$store_params[] = [
 //    		'key' => 'guid',
 //    		'value' => "= {$user->chain_store}",
 //    		'operation' => ''
 //    	];
 //    	$store = ShopsService::getInstance()->getStoresOnView($store_params,0,1);
 //    	$shop_params[] = [
	//     	'key' => 'guid',
	//     	'value' => "= {$store->owner_guid}",
	//     	'operation' => ''
	//     ];
 //    } else {
	//     $shop_params[] = [
	//     	'key' => 'owner_guid',
	//     	'value' => "= {$user->guid}",
	//     	'operation' => ''
	//     ];
 //    }
 //    $shop = ShopsService::getInstance()->getShopOnView($shop_params,0,1);
 //    if ($shop) {
	//     if (is_array($shops_liked_guid) && count($shops_liked_guid) > 0) {
	// 	    if (in_array($shop->guid, $shops_liked_guid)) {
	// 	    	$shop->liked = true;
	// 	    }
	//     }
	//     if ($user->chain_store) {
	//     	$shop->shop_address = $store->address;
	// 		$shop->shop_phone = $store->phone;
	// 		$shop->shop_province = $store->shop_province;
	// 		$shop->shop_district = $store->shop_district;
	// 		$shop->shop_ward = $store->shop_ward;
	// 		$shop->full_address = $store->full_address;
	//     }
 //    } else {
 //    	$shop = new OssnObject;
 //    	$shop->status = false;
 //    }
	// $user->shop = $shop;
	
	// $feed_params = null;
	// $feed_params[] = [
	// 	'key' => 'poster_guid',
	// 	'value' => "= {$user->guid}",
	// 	'operation' => ''
	// ];
	// $feed_params[] = [
	// 	'key' => 'access',
	// 	'value' => "IN (2,3)",
	// 	'operation' => 'AND'
	// ];
	// $feed_params[] = [
	// 	'key' => 'time_created',
	// 	'value' => "DESC",
	// 	'operation' => 'order_by'
	// ];

	// $feed = (new OssnWall)->getPostOnView($feed_params,0,1);
	// if ($feed) {
	//     $thought = json_decode(html_entity_decode($feed->description));
	//     $user->thought = $thought->post;
	// 	if (!empty($feed->mood)) {
	// 		$mood = ossn_get_object($feed->mood);
	// 		// $mood->icon = market_photo_url($shop_guid, $mood->{'file:mood:icon'}, "mood");
	// 		// unset($mood->{'file:mood:icon'});
	// 		$user->mood = $mood;
	// 	}
	// }

	// return $user;
});