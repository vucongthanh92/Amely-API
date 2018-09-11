<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->get($container['prefix'].'/profile', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	// $likes = new OssnLikes;
	// $user = null;
	$params = $request->getQueryParams();
	$loggedin_user = loggedin_user();
	$user_params = null;

	if (array_key_exists("guid", $params) && is_numeric($params['guid'])) {
		$user_params[] = [
			'key' => 'guid',
			'value' => "= {$params['guid']}",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1);
	} else if (array_key_exists("username", $params) && $params['username'] != null) {
		$user_params[] = [
			'key' => 'username',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1);
	} else {
		$user = $loggedin_user;
	}
	if (!array_key_exists("type", $params)) $params['type'] = "default";
	switch ($params['type']) {
		case 'notification':
			break;
		default:
			$shop_params = null;
		    if ($user->chain_store) {
		    	$store_params = null;
		    	$store_params[] = [
		    		'key' => 'guid',
		    		'value' => "= {$user->chain_store}",
		    		'operation' => ''
		    	];
		    	$store = $select->getStores($store_params,0,1);
		    	$shop_params[] = [
			    	'key' => 'guid',
			    	'value' => "= {$store->owner_guid}",
			    	'operation' => ''
			    ];
		    } else {
			    $shop_params[] = [
			    	'key' => 'owner_guid',
			    	'value' => "= {$user->guid}",
			    	'operation' => ''
			    ];
		    }
		    $shop = $select->getShops($shop_params,0,1);
		    if ($shop) {
			    $like_params = null;
			    $like_params[] = [
			    	'key' => 'guid',
			    	'value' => "= {$loggedin_user->guid}",
			    	'operation' => ''
			    ];
			    $like_params[] = [
			    	'key' => 'type',
			    	'value' => "= 'shop'",
			    	'operation' => 'AND'
			    ];
			    $like_params[] = [
			    	'key' => 'subject_id',
			    	'value' => "= {$shop->guid}",
			    	'operation' => 'AND'
			    ];
			    $is_liked_shop = $select->getLikes($like_params,0,1);
			    if ($is_liked_shop) {
			    	$shop->liked = true;
			    }
			    if ($user->chain_store) {
			    	$shop->shop_address = $store->address;
					$shop->shop_phone = $store->phone;
					$shop->shop_province = $store->shop_province;
					$shop->shop_district = $store->shop_district;
					$shop->shop_ward = $store->shop_ward;
					$shop->full_address = $store->full_address;
			    }
			    $user->shop = $shop;
		    }
		    $relation_params = null;
		    $relation_params[] = [
		    	'key' => 'relation_from',
		    	'value' => "= {$loggedin_user->guid}",
		    	'operation' => ''
		    ];
		    $relation_params[] = [
		    	'key' => 'relation_to',
		    	'value' => "= {$user->guid}",
		    	'operation' => 'AND'
		    ];
		    $relation_params[] = [
		    	'key' => 'type',
		    	'value' => "= 'friend:request'",
		    	'operation' => 'AND'
		    ];
		    $relation = $select->getRelationships($relation_params,0,1);
		    if ($relation) {
		        $user->requested = 1;
		    }
			break;
	}

	if ($user->guid != $loggedin_user->guid) {
		$block_list = 0;
		if (property_exists($loggedin_user, 'blockedusers')) {
			$block_list = json_decode($loggedin_user->blockedusers);
		}
	    if (is_array($block_list) && count($block_list) > 0) {
		    if (in_array($user->guid, $block_list)) {
		    	return response([
					"status"  => false,
					"error"   => "User is blocked"
				]);
		    }
	    }
	}

	return response($user);
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