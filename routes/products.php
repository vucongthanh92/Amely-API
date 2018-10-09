<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productDetailService = ProductDetailService::getInstance();
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
	$product_detail = $productDetailService->getDetailProductByType($product->owner_id, 'id');
	if (!$product_detail) return response(false);
	$product_display = (object) array_merge((array) $product, (array) $product_detail);
	
	$product_stores = $productStoreService->getQuantityByType($product->id, 'product_id');
	$stores = array_map(create_function('$o', 'return $o->store_id;'), $product_stores);
	$stores = implode(',', $stores);

	$shop = $shopService->getShopByType($product_detail->owner_id, 'id');
	if (!$shop) return response(false);

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

	$product_display->shop = $shop;
	if ($product_display->category) {
		$categories_id = $product_display->category;
		if ($categories_id) {
			$category_params = null;
			$category_params[] = [
				'key' => 'id',
				'value' => "IN ({$categories_id})",
				'operation' => ''
			];
			$categories = $categoryService->getCategories($category_params, 0, 99999999);
			if (!$categories) return response(false);
			$product_display->categories = $categories;
		}
	}
	if (!$product_display) response(false);
	return response($product_display);
});

$app->post($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$productDetailService = ProductDetailService::getInstance();
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
	$pdetails = $productDetailService->getDetailProducts($product_params, $offset, $limit);
	if (!$pdetails) return response(false);
	$pdetails_id = $categories_id = [];
	foreach ($pdetails as $key => $pdetail) {
		if ($pdetail->category) {
			$arr = explode(',', $pdetail->category);
			$categories_id = array_merge((array)$categories_id, (array)$arr);
		}
		if (!in_array($pdetail->id, $pdetails_id)) {
			array_push($pdetails_id, $pdetail->id);
		}
	}

	if (!$pdetails_id) return response(false);
	$pdetails_id = implode(',', $pdetails_id);
	$sub_params = null;
	$sub_params[] = [
		'key' => 'time_created',
		'value' => "DESC",
		'operation' => 'order_by'
	];
	$sub_params[] = [
		'key' => 'enabled',
		'value' => "= 1",
		'operation' => ''
	];
	$sub_params[] = [
		'key' => 'owner_id',
		'value' => "IN ($pdetails_id)",
		'operation' => 'AND'
	];
	$products = $productService->getProducts($sub_params, 0, 999999999);
	if (!$products) return response(false);

	$responses = [];
	foreach ($products as $product) {
		foreach ($pdetails as $pdetail) {
			if ($product->owner_id == $pdetail->id) {
				$pdetail = (object) array_merge((array) $product, (array) $pdetail);
				$responses[] = $pdetail;
			}
		}
	}

	if ($categories_id) {
		$categories = [];
		$categories_id = array_unique($categories_id);
		if ($categories_id) {
			$categories_id = implode(',', $categories_id);
			$categories = $categoryService->getCategoriesByType($categories_id, 'id');
			foreach ($responses as $key => $response) {
				if ($categories) $response->categories = $categories;
				$responses[$key] = $response;
			}
		}
	}

	return response(array_values($responses));
});

$app->put($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productDetailService = ProductDetailService::getInstance();
	$productService = ProductService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$storeService = StoreService::getInstance();


	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	$num = rand();
	$pdetail = new ProductDetail();
	$pdetail->data->owner_id = 1;
	$pdetail->data->type = "shop";
	$pdetail->data->title = "product detail ".$num;
	$pdetail->data->sku = "sku-p".$num;
	$pdetail->data->friendly_url = "product-detail-".$num;
	$pdetail->data->is_special = 0;
	$pdetail->data->creator_id = $loggedin_user->id;
	$pdetail->data->enabled = 1;
	$pdetail->data->category = "1,2,3,4,5,6";
	$pdetail->data->approved = time();
	$pdetail_id = $pdetail->insert(true);
	$productDetailSnapshot_id = false;
	if (!$pdetail_id) return response(['pdetail_id' => false]);
	$pdetail = $productDetailService->getDetailProductByType($pdetail_id, 'id', false);
	if (!$pdetail) return response(['pdetail' => false]);
	$key = $snapshotService->generateSnapshotKey($pdetail, 'detail');
	$snapshot = $snapshotService->checkExistKey($key, 'detail');
	if ($snapshot) {
		$productDetailSnapshot_id = $snapshot->id;
	} else {
		$productDetailSnapshot = new ProductDetailSnapshot();
		foreach ($pdetail as $pkey => $pvalue) {
			$productDetailSnapshot->data->$pkey = $pvalue;
		}
		unset($productDetailSnapshot->data->id);
		$productDetailSnapshot->data->code = $key;
		$productDetailSnapshot_id = $productDetailSnapshot->insert(true);
	}
	
	if (!$productDetailSnapshot_id) return response(['productDetailSnapshot_id' => false]);
	$pdetail = new ProductDetail();
	$pdetail->id = $productDetailSnapshot_id;
	$pdetail->data->pdetail_snapshot = $productDetailSnapshot_id;
	$pdetail->update();

	$product = new Product();
	$product->data->owner_id = $pdetail_id;
	$product->data->type = 'product_detail';
	$product->data->title = "product".$num;
	$product->data->description = "product".$num;
	$product->data->price = 15000;
	$product->data->sku = "sku-sub-p1";
	$product->data->creator_id = $loggedin_user->id;
	$product->data->enabled = 1;
	$product->data->approved = time();
	$product_id = $product->insert(true);
	$product_snapshot_id = false;
	if (!$product_id) return response(['product_id' => false]);
	$product = $productService->getProductByType($product_id, 'id', false);
	if (!$product) return response(['product' => false]);
	$key = $snapshotService->generateSnapshotKey($product, 'product');
	$snapshot = $snapshotService->checkExistKey($key, 'product');
	if ($snapshot) {
		$product_snapshot_id = $snapshot->id;
	} else {
		$productSnapshot = new ProductSnapshot();
		foreach ($product as $spkey => $spvalue) {
			$productSnapshot->data->$spkey = $spvalue;
		}
		unset($productSnapshot->data->id);
		$productSnapshot->data->owner_id = $productDetailSnapshot_id;
		$productSnapshot->data->type = "pdetail_snapshot";
		$productSnapshot->data->code = $key;
		$product_snapshot_id = $productSnapshot->insert(true);
		
	}
	$product = new Product();
	$product->id = $product_id;
	$product->data->product_snapshot = $product_snapshot_id;
	$product->where = "id = $product_id";
	$product->update();

	$store = $storeService->getStoreByType(1, 'owner_id');
	$product_store = new ProductStore();
	$product_store->data->owner_id = 1;
	$product_store->data->type = 'shop';
	$product_store->data->store_id = 1;
	$product_store->data->product_id = $product_id;
	$product_store->data->creator_id = $loggedin_user->id;
	$product_store->data->quantity = 100;
	$product_store->insert();

	return response(true);

});