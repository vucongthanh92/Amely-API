<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	if ($loggedin_user->usercurrency)
		$currency_code = $loggedin_user->usercurrency;
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists("guid", $params)) $params["guid"] = false;
	if (!array_key_exists("qrcode", $params)) $params["qrcode"] = false;

	$guid = $params["guid"];
	$qrcode = $params["qrcode"];
	$product = null;
	if ($qrcode) {
		$friendly_url = array_pop(explode("/", $qrcode));

		$product_params = null;
		$product_params[] = [
			'key' => 'friendly_url',
			'value' => "= {$friendly_url}",
			'operation' => ''
		];
		$product = $select->getProducts($product_params,0,1);
	}
	if ($guid)) {
		$product_params = null;
		$product_params[] = [
			'key' => 'guid',
			'value' => "= {$guid}",
			'operation' => ''
		];
		$product = $select->getProducts($product_params,0,1);
	}
	if (!$product) return response(false);
	$shop_params = null;
	$shop_params[] = [
		'key' => 'guid',
		'value' => "= {$product->owner_guid}",
		'operation' => ''
	];
	$shop = $select->getShops($shop_params,0,1);
	if (!$shop) return response(false);
	
	if ($product->manufacturer) {
		$manufacturer_params = null;
		$manufacturer_params[] = [
			'key' => 'guid',
			'value' => "= {$product->manufacturer}",
			'operation' => ''
		];

        $manufacturer = $select->getManufacturers($manufacturer_params,0,1);
        if ($manufacturer) {
        	$product->manufacturer = $manufacturer->title;
        }
    }
	$product->category = array_filter($product->category, function($value) { return $value !== ''; });
	$categories_guid = implode(",", $product->category);
	if ($categories_guid) {
		$category_params = null;
		$category_params[] = [
			'key' => 'guid',
			'value' => "IN ({$categories_guid})",
			'operation' => ''
		];
		$categories_query = $select->getCategories($category_params,0,99999999);
		foreach ($categories_query as $key => $category_query) {
			$categories[$category_query->guid] = $category_query;
		}

	} else {
		$categories = [];
	}

	return response([
		"product"    => $product,
		"categories" => $categories,
		"shop" => $shop
	]);
});

$app->post($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	if ($loggedin_user->usercurrency)
		$currency_code = $loggedin_user->usercurrency;
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("shop_guid", $params)) 		$params["shop_guid"] = false;
	if (!array_key_exists("type_product", $params)) 	$params["type_product"] = false;
	if (!array_key_exists("category_guid", $params)) 	$params["category_guid"] = false;
	if (!array_key_exists("product_filter", $params)) 	$params["product_filter"] = false;
	if (!array_key_exists("get_all", $params)) 			$params["get_all"] = false;
	if (!array_key_exists("offset", $params)) 			$params["offset"] = 0;
	if (!array_key_exists("limit", $params)) 			$params["limit"] = 10;
	if (!array_key_exists("product_number", $params)) 	$params["product_number"] = 10;


	$shop_guid = $params["shop_guid"];
	$type_product = $params["type_product"];
	$category_guid = $params["category_guid"];
	$product_filter = $params["product_filter"];
	$get_all = $params["get_all"];
	$offset = $params["offset"];
	$limit = $params["limit"];
	$product_number = $params["product_number"];
	$limit = $product_number;
	$params = $categories = $manufacturers = [];

	$product_params[] = [
		'key' => 'guid',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	
	if ($get_all) {
		$product_params[] = [
			'key' => 'owner_guid',
			'value' => "= {$shop_guid}",
			'operation' => ''
		];

		if ($category_guid) {
			$product_params[] = [
				'key' => "FIND_IN_SET({$category_guid}, category)",
				'value' => '',
				'operation' => 'AND'
			];
		}
	} else if ($category_guid) {
		$product_params[] = [
			'key' => 'approved',
			'value' => "not in ('new','suspended','unpublished')",
			'operation' => ''
		];

		$product_params[] = [
			'key' => "FIND_IN_SET({$category_guid}, category)",
			'value' => '',
			'operation' => 'AND'
		];

	} else {
		if ($shop_guid) {
			$product_params[] = [
				'key' => 'approved',
				'value' => "not in ('new','suspended','unpublished')",
				'operation' => ''
			];
			$product_params[] = [
				'key' => 'owner_guid',
				'value' => "= {$shop_guid}",
				'operation' => 'AND'
			];
		} else {
			return false;
		}
	}
	switch ($type_product) {
		case 'featured':
			$product_params[] = [
				'key' => 'featured',
				'value' => "= 1",
				'operation' => 'AND'
			];
			break;
		case 'default':
			$product_params[] = [
				'key' => 'is_special',
				'value' => "not in ('1','2')",
				'operation' => 'AND'
			];
			break;
		case 'voucher':
			$product_params[] = [
				'key' => 'is_special',
				'value' => "= 1",
				'operation' => 'AND'
			];
			break;
		case 'ticket':
		    $product_params[] = [
				'key' => 'is_special',
				'value' => "= 2",
				'operation' => 'AND'
			];
			break;
		default:
			break;
	}
	$product_params[] = [
		'key' => "enabled",
		'value' => "= 1",
		'operation' => 'AND'
	];
	if ($product_filter) {
		$product_params[] = [
			'key' => 'product_group',
			'value' => "= {$product_filter}",
			'operation' => 'AND'
		];
	}

	$product_params[] = [
		'key' => '',
		'value' => 'lgthumb',
		'operation' => 'image'
	];

	$products = $select->getProducts($product_params, $offset, $limit);
	if (!$products) return response(false);
	$categories = $categories_guid = $shop_categories = $market_categories = $voucher_categories = $manufacturers = $manufacturers_guid = [];
	foreach ($products as $kproduct => $product) {
		foreach ($product->category as $kcategory => $vcategory) {
			if ($vcategory) {
				if (!in_array($vcategory, $categories_guid)) {
					$categories_guid[] = $vcategory;
				}
			}
		}

		if (!in_array($product->manufacturer, $manufacturers_guid)) {
			if ($product->manufacturer) {
				$manufacturers_guid[] = $product->manufacturer;
			}
		}
	}

	$categories_guid = array_unique($categories_guid);
	if ($categories_guid) {
		$categories_guid = implode(',', $categories_guid);
		$category_params = null;
		$category_params[] = [
			'key' => 'guid',
			'value' => "IN ({$categories_guid})",
			'operation' => ''
		];
		$categories = $select->getCategories($category_params,0,99999999);
		foreach ($categories as $key => $category) {
			$categories[$category->guid] = $category;
		}
	}

	$manufacturers_guid = array_unique($manufacturers_guid);
	if ($manufacturers_guid) {
		$manufacturers_guid = implode(',', $manufacturers_guid);
		$manufacturer_params = null;
		$manufacturer_params[] = [
			'key' => 'guid',
			'value' => "IN ({$manufacturers_guid})",
			'operation' => ''
		];
		$manufacturers = $select->getManufacturers($manufacturer_params,0,9999999);
		foreach ($manufacturers as $key => $manufacturer) {
			$manufacturers[$manufacturer->guid] = $manufacturer;
		}
	}

	if (count($products) < $product_number) {
		$offset = -1;
	} else {
		$offset = $offset + $product_number;
	}
	return response([
		"products" => $products,
		"categories" => $categories,
		"manufacturer" => $manufacturers,
		"offset" => $offset
	]);
});