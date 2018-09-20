<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// $app->get('/authtoken', function (Request $request, Response $response, array $args) {
$app->get($container['prefix'].'/profile', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$params = $request->getQueryParams();
	$loggedin_user = loggedin_user();
	$user_params = null;

	if (array_key_exists("guid", $params) && is_numeric($params['guid'])) {
		$user_params[] = [
			'key' => 'id',
			'value' => "= {$params['guid']}",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1,false);
	} else if (array_key_exists("username", $params) && $params['username'] != null) {
		$user_params[] = [
			'key' => 'username',
			'value' => "= '{$params['username']}'",
			'operation' => ''
		];
		$user = $select->getUsers($user_params,0,1,false);
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

	return response($user);
});