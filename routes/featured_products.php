<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/featured_products', function (Request $request, Response $response, array $args) {

	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	$shops_block = 0;
	if ($loggedin_user->usercurrency)
		$currency_code = $loggedin_user->usercurrency;

	$ads_params = null;
	$ads_params = conditionAds();
	$ads_params[] = [
		'key' => 'advertise_type',
		'value' => "= 'product'",
		'operation' => 'AND'
	];
    $ads_params[] = [
    	'key' => 'cpc',
    	'value' => "DESC",
    	'operation' => 'order_by'
    ];
	$products_ads = $select->getAdvertisements($ads_params, 0, 16);
	if (!$products_ads) return response(false);
	$ads = $shops_guid = $products_guid = [];
	foreach ($products_ads as $key => $product_ads) {
		if (!in_array($product_ads->owner_guid, $shops_guid)) {
			array_push($shops_guid, $product_ads->owner_guid);
		}
	}
	$shops_guid = implode(",", array_unique($shops_guid));

	$shop_params = null;
	$shop_params[] = [
		'key' => 'status',
		'value' => "= 3",
		'operation' => ''
	];
	$shop_params[] = [
		'key' => 'guid',
		'value' => "IN ({$shops_guid})",
		'operation' => 'AND'
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
	
	$shops = $select->getShops($shop_params,0,9999999, false);
	if (!$shops) return response(false);
	$shops_guid = array_map(create_function('$o', 'return $o->guid;'), $shops);
	foreach ($products_ads as $key => $product_ads) {
		if (!in_array($product_ads->owner_guid, $shops_guid)) {
			unset($products_ads[$key]);
			continue;
		}
		if (!in_array($product_ads->item, $products_guid)) {
			array_push($products_guid, $product_ads->item);
		}
		$ads[$product_ads->owner_guid] = $product_ads->guid;
	}
	if (!$products_ads) return response(false);
	$products_guid = implode(',', array_unique($products_guid));
	$product_params = null;
	$product_params[] = [
		'key' => 'guid',
		'value' => "IN {$products_guid}",
		'operation' => ''
	];
	$products = $select->getProducts($product_params,0,99999999);
	if (!$products) return response(false);
	foreach ($products as $key => $product) {

		$product->advertise_guid = $ads[$product->owner_guid];
		$product_ads[$key] = $product;
	}
	return response(array_values($products));
});