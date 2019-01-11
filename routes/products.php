<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$categoryService = CategoryService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) $params['id'] = false;
	if (!array_key_exists('qrcode', $params)) $params['qrcode'] = false;
	$input = $type = false;
	if ($params['qrcode']) {
		$input = $friendly_url;
		$type = 'friendly_url';
	} else {
		$input = $params['id'];
		$type = 'id';
	}
	if (!$input && !$type) return response(false);
	$product = $productService->getProductByType($input, $type);
	if (!$product) return response(false);
	
	$product_stores = $productStoreService->getQuantityByType($product->id, 'product_id');
	$stores = array_map(create_function('$o', 'return $o->store_id;'), $product_stores);
	$stores = implode(',', $stores);

	$shop = $shopService->getShopByType($product->owner_id, 'id');
	if (!$shop) return response(false);
	if (!$stores) return response(false);

	$stores = $storeService->getStoresByType($stores, 'id');
	foreach ($stores as $key => $store) {
		foreach ($product_stores as $product_store) {
			if ($product_store->store_id == $store->id) {
				$store->quantity = $product_store->quantity;
			}
		}
		$stores[$key] = $store;
	}
	$shop->stores = $stores;

	$product->shop = $shop;
	if ($product->category) {
		$categories_id = $product->category;
		if ($categories_id) {
			$category_params = null;
			$category_params[] = [
				'key' => 'id',
				'value' => "IN ({$categories_id})",
				'operation' => ''
			];
			$categories = $categoryService->getCategories($category_params, 0, 99999999);
			if (!$categories) return response(false);
			$product->categories = $categories;
		}
	}
	if (!$product) return response(false);
	return response($product);
});

$app->post($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$categoryService = CategoryService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) 		$params['shop_id'] = false;
	if (!array_key_exists('type_product', $params)) 	$params['type_product'] = 'default';
	if (!array_key_exists('category_id', $params)) 	$params['category_id'] = false;
	if (!array_key_exists('product_filter', $params)) 	$params['product_filter'] = false;
	if (!array_key_exists('offset', $params)) 			$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 			$params['limit'] = 10;

	$shop_id = $params['shop_id'];
	$type_product = $params['type_product'];
	$category_id = $params['category_id'];
	$product_filter = $params['product_filter'];
	$offset = $params['offset'];
	$limit = $params['limit'];

	$product_params[] = [
		'key' => 'p.id',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	$product_params[] = [
		'key' => "p.status",
		'value' => "= 1",
		'operation' => ''
	];
	if ($product_filter) {
		$product_params[] = [
			'key' => 'p.product_group',
			'value' => "= {$product_filter}",
			'operation' => 'AND'
		];
	}

	if ($category_id) {
		$product_params[] = [
			'key' => 'p.approved',
			'value' => "> 0",
			'operation' => 'AND'
		];

		$product_params[] = [
			'key' => "FIND_IN_SET({$category_id}, p.category)",
			'value' => '',
			'operation' => 'AND'
		];

	} else {
		if ($shop_id) {
			$product_params[] = [
				'key' => 'p.approved',
				'value' => "> 0",
				'operation' => 'AND'
			];
			$product_params[] = [
				'key' => 'p.owner_id',
				'value' => "= {$shop_id}",
				'operation' => 'AND'
			];
		}
	}
	switch ($type_product) {
		case 'all':
			break;
		case 'featured':
			$product_params[] = [
				'key' => 'p.featured',
				'value' => "= 1",
				'operation' => 'AND'
			];
			break;
		case 'default':
			$product_params[] = [
				'key' => 'p.is_special',
				'value' => "= 0",
				'operation' => 'AND'
			];
			break;
		case 'voucher':
			$product_params[] = [
				'key' => 'p.is_special',
				'value' => "= 1",
				'operation' => 'AND'
			];
			break;
		case 'ticket':
		    $product_params[] = [
				'key' => 'p.is_special',
				'value' => "= 2",
				'operation' => 'AND'
			];
			break;
		default:
			break;
	}
	$product_params[] = [
        'key' => 'amely_product_store ps',
        'value' => "ps.product_id = p.id",
        'operation' => 'JOIN'
    ];
    $product_params[] = [
        'key' => 'ps.quantity',
        'value' => "> 0",
        'operation' => 'AND'
    ];
    $product_params[] = [
    	'key' => 'ps.quantity',
    	'value' => '',
    	'operation' => 'query_params'
    ];
    $product_params = $productService->queryProductParams($product_params);

	$products = $productService->getProducts($product_params, $offset, $limit);
	if (!$products) return response(false);

	$categories = $categories_id = [];
	foreach ($products as $product) {
		if ($product->category) {
			$arr = explode(',', $product->category);
			$categories_id = array_merge((array)$categories_id, (array)$arr);
		}
	}

	if ($categories_id) {
		$categories_id = array_unique($categories_id);
		if ($categories_id) {
			$categories_id = implode(',', $categories_id);
			$categories = $categoryService->getCategoriesByType($categories_id, 'id');
			foreach ($products as $key => $product) {
				
				if ($params['shop_id']) {
					$product->quantity = 0;
					$store_quantity = ProductStoreService::getInstance()->checkQuantityInStore($product->id, $loggedin_user->chain_store);
					if ($store_quantity) {
						$product->quantity = $store_quantity->quantity;
					}
				}
				if (!$params['shop_id']) {
					$store_quantity = ProductStoreService::getInstance()->showProduct($product->id);
					if (!$store_quantity) {
						unset($products[$key]);
						continue;
					}
				}
				if ($categories) $product->categories = $categories;
				$products[$key] = $product;
			}
		}
	}
	if (!$products) return response(false);
	return response(array_values($products));
});

$app->put($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$storeService = StoreService::getInstance();
	$shopService = ShopService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$loggedin_user = loggedin_user();

	$shop = $shopService->getShopByType($loggedin_user->id, 'owner_id', false);
	if (!$shop) return response(false);
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $shop->id;
	if (!array_key_exists('type', $params)) $params['type'] = 'shop';
	if (!array_key_exists('title', $params)) $params['title'] = 0;
	if (!array_key_exists('description', $params)) $params['description'] = 0;
	if (!array_key_exists('sku', $params)) $params['sku'] = 0;
	if (!array_key_exists('price', $params)) $params['price'] = 0;
	if (!array_key_exists('model', $params)) $params['model'] = 0;
	if (!array_key_exists('tag', $params)) $params['tag'] = 0;
	if (!array_key_exists('tax', $params)) $params['tax'] = 0;
	if (!array_key_exists('friendly_url', $params)) $params['friendly_url'] = 0;
	if (!array_key_exists('weight', $params)) $params['weight'] = 0;
	if (!array_key_exists('expiry_type', $params)) $params['expiry_type'] = 0;
	if (!array_key_exists('currency', $params)) $params['currency'] = 0;
	if (!array_key_exists('origin', $params)) $params['origin'] = 0;
	if (!array_key_exists('product_order', $params)) $params['product_order'] = 0;
	if (!array_key_exists('duration', $params)) $params['duration'] = 0;
	if (!array_key_exists('storage_duration', $params)) $params['storage_duration'] = 0;
	if (!array_key_exists('is_special', $params)) $params['is_special'] = 0;
	if (!array_key_exists('product_group', $params)) $params['product_group'] = 0;
	if (!array_key_exists('custom_attributes', $params)) $params['custom_attributes'] = 0;
	if (!array_key_exists('begin_day', $params)) $params['begin_day'] = 0;
	if (!array_key_exists('end_day', $params)) $params['end_day'] = 0;
	if (!array_key_exists('manufacturer', $params)) $params['manufacturer'] = 0;
	if (!array_key_exists('sale_price', $params)) $params['sale_price'] = 0;
	if (!array_key_exists('unit', $params)) $params['unit'] = 0;
	if (!array_key_exists('category', $params)) $params['category'] = 0;
	if (!array_key_exists('adjourn_price', $params)) $params['adjourn_price'] = 0;
	if (!array_key_exists('images', $params)) $params['images'] = 0;
	if (!array_key_exists('parent_id', $params)) $params['parent_id'] = 0;

	$num = rand();
	if (!$params['title'] || !$params['sku']) return response(false);

	$product = $productService->checkSKU($params['sku']);
	$data = [];
	if ($product) return response(false);
	if ($params['tag']) {
		$params['tag'] = implode(',', $params['tag']);
	}
	if ($params['images']) {
		$params['images'] = implode(',', $params['images']);
	}
	if ($params['begin_day']) {
		$params['begin_day'] = strtotime($params['begin_day']);
	}
	if ($params['end_day']) {
		$params['end_day'] = strtotime($params['end_day']);
	}
	if ($params['category']) {
		$data['category'] = implode(',', $params['category']);
	}
	$data['owner_id'] = $params['owner_id'];
	$data['type'] = 'shop';
	$data['title'] = $params['title'];
	$data['description'] = $params['description'];
	$data['sku'] = $params['sku'];
	$data['price'] = $params['price'];
	$data['model'] = $params['model'];
	$data['tag'] = $params['tag'];
	$data['tax'] = $params['tax'];
	$data['friendly_url'] = $params['friendly_url'];
	$data['weight'] = $params['weight'];
	$data['expiry_type'] = $params['expiry_type'];
	$data['currency'] = $params['currency'];
	$data['origin'] = $params['origin'];
	$data['product_order'] = 0;
	$data['duration'] = $params['duration'];
	$data['storage_duration'] = $params['storage_duration'];
	$data['is_special'] = $params['is_special'];
	$data['product_group'] = $params['product_group'];
	$data['creator_id'] = $loggedin_user->id;
	$data['custom_attributes'] = $params['custom_attributes'];
	$data['download'] = $params['download'];
	$data['featured'] = $params['featured'];
	$data['begin_day'] = $params['begin_day'];
	$data['end_day'] = $params['end_day'];
	$data['manufacturer'] = $params['manufacturer'];
	$data['sale_price'] = 0;
	$data['unit'] = $params['unit'];
	$data['approved'] = 0;
	$data['enabled'] = 0;
	$data['adjourn_price'] = $params['adjourn_price'];
	$data['images'] = $params['images'];

	$product_id = $productService->save($data);
	
	$product_params = null;
	$product_params[] = [
		'key' => 'id',
		'value' => "= {$product_id}",
		'operation' => ''
	];
	$product_properties = $productService->getPropertyProduct($product_params);
	if (!$product_properties) return response(false);
	$key = $snapshotService->generateSnapshotKey($product_properties);
	$snapshot = $snapshotService->checkExistKey($key);
	
	if ($snapshot) {
		$snapshot_id = $snapshot->id;
	} else {
		$data = null;
		$data = [];
		foreach ($product_properties as $property => $product_property) {
			$data[$property] = $product_property;
		}
		unset($data['id']);
		$data['code'] = $key;
		$snapshot_id = $snapshotService->insert(true);
	}
	$product = new Product();
	$product->data->snapshot_id = $snapshot_id;
	$product->where = "id = {$product_id}";
	$product->update();

	$product_store = $productStoreService->checkQuantityInStore($product_id, 1);

	$store = $storeService->getStoreByType(1, 'owner_id');
	if ($product_store) {

	} else {
		$product_store = new ProductStore();
		$product_store->data->owner_id = 1;
		$product_store->data->type = 'shop';
		$product_store->data->store_id = 1;
		$product_store->data->product_id = $product_id;
		$product_store->data->creator_id = $loggedin_user->id;
		$product_store->data->quantity = 100;
		$product_store->insert();
	}

	return response(true);
});