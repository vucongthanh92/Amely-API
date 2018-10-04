<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$subProductService = SubProductService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$categoryService = CategoryService::getInstance();
	
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
	$sproduct = $subProductService->getSubProductByType($input, $type);
	if (!$sproduct) return response(false);
	$product = $productService->getProductByType($sproduct->owner_id, 'id');
	if (!$product) return response(false);
	$product = (object) array_merge((array) $sproduct, (array) $product);
	
	// $store = $storeService->getStoreByType($product->owner_id, 'id');
	// if (!$store) return response(false);

	$shop = $shopService->getShopByType($product->owner_id, 'id');
	if (!$shop) return response(false);
	// $shop = (object) array_merge((array) $store, (array) $shop);



	$product->shop = $shop;
	if ($product->category) {
		$categories_guid = implode(",", $product->category);
		if ($categories_guid) {
			$category_params = null;
			$category_params[] = [
				'key' => 'guid',
				'value' => "IN ({$categories_guid})",
				'operation' => ''
			];
			$categories = $categoryService->getCategories($category_params, 0, 99999999);
			if (!$categories) return response(false);
			$product->categories = $categories;
		}
	}
	if (!$product) response(false);
	return response($product);
});

$app->post($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$subProductService = SubProductService::getInstance();
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
				'operation' => ''
			];
			$product_params[] = [
				'key' => 'owner_guid',
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
	$products_id = $categories_id = [];
	foreach ($products as $key => $product) {
		if ($product->category) {
			$arr = explode(',', $product->category);
			$categories_id = array_merge((array)$categories_id, (array)$arr);
		}
		if (!in_array($product->id, $products_id)) {
			array_push($products_id, $product->id);
		}
	}

	if (!$products_id) return response(false);
	$products_id = implode(',', $products_id);
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
		'value' => "IN ($products_id)",
		'operation' => 'AND'
	];
	$subproducts = $subProductService->getSubProducts($sub_params, 0, 999999999);
	if (!$subproducts) return response(false);

	$responses = [];
	foreach ($subproducts as $subproduct) {
		foreach ($products as $product) {
			if ($subproduct->owner_id == $product->id) {
				$product = (object) array_merge((array) $subproduct, (array) $product);
				$responses[] = $product;
			}
		}
	}



	if ($categories_id) {
		$categories = [];
		$categories_id = array_unique($categories_id);
		if ($categories_id) {
			$categories_id = implode(',', $categories_id);
			$category_params = null;
			$category_params[] = [
				'key' => 'id',
				'value' => "IN ({$categories_id})",
				'operation' => ''
			];
			$categories = $select->getCategories($category_params,0,99999999);
			foreach ($responses as $key => $response) {
				$response->categories = $categories;
				$responses[$key] = $response;
			}
		}
	}

	return response(array_values($responses));
});

$app->put($container['prefix'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$subProductService = SubProductService::getInstance();

	$snapshotService = SnapshotService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];

	$product = new Product();
	$product->data->owner_id = 1;
	$product->data->type = "shop";
	$product->data->title = "product 1";
	$product->data->description = "p1";
	$product->data->sku = "sku-p1";
	$product->data->friendly_url = "product-1";
	$product->data->is_special = 0;
	$product->data->creator_id = $loggedin_user->id;
	$product->data->enabled = 1;
	$product->data->approved = time();

	$product_id = $product->insert(true);

	$p = $productService->getProductByType($product_id, 'id', false);
	$key = $snapshotService->generateSnapshotKey($p, 'product');
	$snapshot = $snapshotService->checkExistKey($key, 'product');
	if ($snapshot) {
		$snapshot_id = $snapshot->id;
	} else {
		$snapshot = new Snapshot();
		foreach ($p as $pkey => $pvalue) {
			$snapshot->data->$pkey = $pvalue;
		}
		unset($snapshot->data->id);
		$snapshot->data->code = $key;
		$snapshot_id = $snapshot->insert(true);
	}
	$product = new Product();
	$product->id = $product_id;
	$product->data->current_snapshot = $snapshot_id;
	$product->update();

	$subp = new SubProduct();
	$subp->data->owner_id = $product_id;
	$subp->data->type = 'product';
	$subp->data->title = "sub p 1";
	$subp->data->description = "sub p 1";
	$subp->data->price = 15000;
	$subp->data->quantity = 100;
	$subp->data->sku = "sku-sub-p1";
	$subp->data->creator_id = $loggedin_user->id;
	$subp->data->enabled = 1;
	$subp->data->approved = time();
	$subp_id = $subp->insert(true);

	$sp = $subProductService->getSubProductByType($subp_id, 'id', false);
	$key = $snapshotService->generateSnapshotKey($sp, 'sub');
	$snapshot = $snapshotService->checkExistKey($key, 'sub');
	if ($snapshot) {
		$subsnapshot_id = $snapshot->id;
	} else {
		$subsnapshot = new SubSnapshot();
		foreach ($sp as $spkey => $spvalue) {
			$subsnapshot->data->$spkey = $spvalue;
		}
		unset($subsnapshot->data->id);
		$subsnapshot->data->owner_id = $subp_id;
		$subsnapshot->data->type = "product_snapshot";
		$subsnapshot->data->code = $key;
		$subsnapshot_id = $subsnapshot->insert(true);
		
	}
	$subp = new SubProduct();
	$subp->id = $subp_id;
	$subp->data->current_sub_snapshot = $subsnapshot_id;
	$subp->where = "id = $subp_id";
	return response($subp->update());

});