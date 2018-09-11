<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/categories', function (Request $request, Response $response, array $args) {

	$select = SlimSelect::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("is_shop", $params)) $params["is_shop"] = false;
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;
	if (!array_key_exists("shop_guid", $params)) $params["shop_guid"] = false;
	if (!array_key_exists("type", $params)) $params["type"] = 0;
	if (!array_key_exists("get_all", $params)) $params["get_all"] = false;

	$is_shop = $params["is_shop"];
	$offset = (double)$params["offset"];
	$limit = (double)$params["limit"];
	$shop_guid = $params["shop_guid"];

	$category_params[] = [
		'key' => 'CAST(`sort_order` AS SIGNED)',
		'value' => "ASC",
		'operation' => 'order_by'
	];

	if (is_numeric($shop_guid)) {
		$category_params[] = [
			'key' => 'owner_guid',
			'value' => "= {$shop_guid}",
			'operation' => ''
		];
		$category_params[] = [
			'key' => 'subtype',
			'value' => "= 'shop:category'",
			'operation' => 'AND'
		];
	} else {
		$type = (int)$params["type"];
		switch ($type) {
			case 2:
				$category_params[] = [
					'key' => 'subtype',
					'value' => "= 'market:voucher_category'",
					'operation' => ''
				];
				break;
			case 3:
				$category_params[] = [
					'key' => 'subtype',
					'value' => "= 'market:ticket_category'",
					'operation' => ''
				];
				break;
			default:
				$category_params[] = [
					'key' => 'subtype',
					'value' => "= 'market:category'",
					'operation' => ''
				];
				break;
		}
	}

	if ($params["get_all"]) {
		$category_params[] = [
			'key' => 'enabled',
			'value' => "= 'true'",
			'operation' => 'AND'
		];
	}
	$categories = $select->getCategories($category_params, $offset, $limit);
	
	foreach ($categories as $key => $category) {
		if ($is_shop) {
			$product_params = null;
			$product_params[] = [
				'key' => "FIND_IN_SET({$category->guid}, category)",
				'value' => '',
				'operation' => ''
			];
			$product_params[] = [
				'key' => '',
				'value' => '',
				'operation' => 'count'
			];

			$products = $select->getProducts($product_params, 0, 1);
	        $category->total_product = $products->count;
		}
	}
	return response($categories);
});