<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/featured_shops', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	// $like_params = null;
	// $like_params[] = [
	// 	'key' => 'guid',
	// 	'value' => "= {$loggedin_user}",
	// 	'operation' => ''
	// ];
	// $like_params[] = [
	// 	'key' => 'type',
	// 	'value' => "= 'shop'",
	// 	'operation' => 'AND'
	// ];
	// $shops_liked = $select->getLikes($like_params, 0, 99999999999);
	// $shops_liked = array_map(create_function('$o', 'return $o->subject_id;'), $shops_liked);

	$ads_params = null;
	$ads_params = conditionAds();
	$ads_params[] = [
		'key' => 'advertise_type',
		'value' => "= 'store'",
		'operation' => 'AND'
	];
    $ads_params[] = [
    	'key' => 'cpc',
    	'value' => "DESC",
    	'operation' => 'order_by'
    ];
	$shops_ads = $select->getAdvertisements($ads_params, 0, 16);
	if (!$shops_ads) return response(false);
	$final_ads = [];

    foreach ($shops_ads as $advertise) {
        $advertise->balance = $advertise->budget - $advertise->amount;
        $final_ads[$advertise->cpc][] = $advertise;
    }
    if (count($final_ads) > 1) {
    	$final_ads = compareAds($final_ads);
    }
    $shops_ads = $shops_guid = [];

    foreach ($final_ads as $advertises) {
        foreach ($advertises as $key => $advertise) {
        	if (!in_array($advertise->item, $shops_guid)) {
        		array_push($shops_guid, $advertise->item);
        	}
        	$shops_ads[$advertise->item] = $advertise->guid;
        }
    }
    $shops_guid = implode(",", array_unique($shops_guid));
	$shop_params = null;
	$shop_params[] = [
		'key' => 'guid',
		'value' => "IN ({$shops_guid})",
		'operation' => ''
	];
	if (property_exists($loggedin_user, 'blockedusers')) {
		$block_list = json_decode($loggedin_user->blockedusers);
		if (is_array($block_list) && count($block_list) > 0) {
			$block_users = implode(',', $block_list);
			$shop_params[] = [
				'key' => 'owner_guid',
				'value' => "NOT IN ({$block_users})",
				'operation' => 'AND'
			];
		}
	}
	$shops = $select->getShops($shop_params,0,9999999);
	if (!$shops) return response(false);
	foreach ($shops as $key => $shop) {
		$shop->advertise_guid = $shops_ads[$shop->guid];
		$shops[$key] = $shop;
	}
	return response(array_values($shops));
});