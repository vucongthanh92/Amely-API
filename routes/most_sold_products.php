<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/most_sold_products', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	if ($loggedin_user->usercurrency) $currency_code = $loggedin_user->usercurrency;

	$block_list = 0;
	if (property_exists($loggedin_user, 'blockedusers')) {
		$block_list = json_decode($loggedin_user->blockedusers);
	}

	$product_list = $params = $categories = $manufacturers = [];

	$product_params = null;
	$product_params[] = [
		'key' => 'number_sold',
		'value' => "> 0",
		'operation' => ''
	];
	$product_params[] = [
		'key' => 'quantity',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'enabled',
		'value' => "= 1",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'approved',
		'value' => "NOT IN ('new', 'suspended')",
		'operation' => 'AND'
	];
	$product_params[] = [
		'key' => 'number_sold',
		'value' => "DESC",
		'operation' => 'order_by'
	];
	$products =  $select->getProductsMarket($product_params,0,16);
	if (!$products) return response(false);

	$products_owner_guid = [];
 	if (is_array($products)) {
 		foreach ($products as $key => $product) {
 			if (!in_array($product->owner_guid, $products_owner_guid)) {
 				array_push($products_owner_guid, $product->owner_guid);
 			}
 		}
 		$products_owner_guid = implode(",", array_unique($products_owner_guid));
 		$shop_params = null;
		$shop_params[] = [
			'key' => 'guid',
			'value' => "IN ({$products_owner_guid})",
			'operation' => ''
		];
		$shop_params[] = [
			'key' => 'status',
			'value' => "= 3",
			'operation' => 'AND'
		];
		$shops = $select->getShops($shop_params,0,9999999);
		if (!$shops) {
			$shops_guid = array_map(create_function('$o', 'return $o->guid;'), $shops);
			foreach ($products as $key => $product) {
				if (!in_array($product->owner_guid, $shops_guid)) {
					unset($products[$key]);
				}
			}
		}
 	}
 	return response(array_values($products));
});