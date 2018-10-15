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
		'key' => 'id',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	$product_params[] = [
		'key' => "enabled",
		'value' => "= 1",
		'operation' => ''
	];
	if ($product_filter) {
		$product_params[] = [
			'key' => 'product_group',
			'value' => "= {$product_filter}",
			'operation' => 'AND'
		];
	}

	if ($category_id) {
		$product_params[] = [
			'key' => 'approved',
			'value' => "REGEXP '^[0-9]+$'",
			'operation' => 'AND'
		];

		$product_params[] = [
			'key' => "FIND_IN_SET({$category_id}, category)",
			'value' => '',
			'operation' => 'AND'
		];

	} else {
		if ($shop_id) {
			$product_params[] = [
				'key' => 'approved',
				'value' => "REGEXP '^[0-9]+$'",
				'operation' => 'AND'
			];
			$product_params[] = [
				'key' => 'owner_id',
				'value' => "= {$shop_id}",
				'operation' => 'AND'
			];
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
				'value' => "= 0",
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
				if ($categories) $product->categories = $categories;
				$products[$key] = $product;
			}
		}
	}

	return response(array_values($products));
});

$app->put($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$storeService = StoreService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	$num = rand();

	$product = new Product();
	$product->data->owner_id = 1;
	$product->data->type = "shop";
	$product->data->title = "product ".$num;
	$product->data->description = "product ".$num;
	$product->data->sku = "sku ".$num;
	$product->data->price = 15000;
	$product->data->snapshot_id = 0;
	$product->data->is_special = 0;
	$product->data->duration = 30;
	$product->data->storage_duration = 30;
	$product->data->creator_id = $loggedin_user->id;
	$product->data->enabled = 1;
	$product->data->category = "1,2,3,4,5,6";
	$product->data->approved = time();
	$product_id = $product->insert(true);
	
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
		$snapshot = new Snapshot();
		foreach ($product_properties as $property => $product_property) {
			$snapshot->data->$property = $product_property;
		}
		unset($snapshot->data->id);
		$snapshot->data->code = $key;
		$snapshot_id = $snapshot->insert(true);
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