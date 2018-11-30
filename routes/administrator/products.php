<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['administrator'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$categoryService = CategoryService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) $params['id'] = false;
	$input = $type = false;
	$input = $params['id'];
	$type = 'id';
	
	if (!$input && !$type) return response(false);
	$product = $productService->getProductByType($input, $type);
	if (!$product) return response(false);
	

	$shop = $shopService->getShopByType($product->owner_id, 'id');
	if (!$shop) return response(false);

	$product_stores = $productStoreService->getQuantityByType($product->id, 'product_id');
	if ($product_stores) {
		$stores_id = array_map(create_function('$o', 'return $o->store_id;'), $product_stores);
		$stores_id = implode(',', $stores_id);
		$stores = $storeService->getStoresByType($stores_id, 'id');
		foreach ($stores as $key => $store) {
			foreach ($product_stores as $product_store) {
				if ($product_store->store_id == $store->id) {
					$store->quantity = $product_store->quantity;
				}
			}
			$stores[$key] = $store;
		}
		$shop->stores = $stores;
	}

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

$app->post($container['administrator'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$storeService = StoreService::getInstance();
	$shopService = ShopService::getInstance();
	$productStoreService = ProductStoreService::getInstance();
	$loggedin_user = loggedin_user();
	
	$params = $request->getParsedBody();
	$files = $request->getUploadedFiles();
    
    
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) $params['id'] = false;
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = false;
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
	if (!array_key_exists('voucher_category', $params)) $params['voucher_category'] = 0;
	if (!array_key_exists('ticket_category', $params)) $params['ticket_category'] = 0;
	if (!array_key_exists('shop_category', $params)) $params['shop_category'] = 0;
	if (!array_key_exists('market_category', $params)) $params['market_category'] = 0;
	if (!array_key_exists('adjourn_price', $params)) $params['adjourn_price'] = 0;
	if (!array_key_exists('images', $params)) $params['images'] = 0;
	if (!array_key_exists('parent_id', $params)) $params['parent_id'] = 0;

	if (!$params['owner_id']) {
		$shop = $shopService->getShopByType($loggedin_user->id, 'owner_id', false);
		if (!$shop) return response(false);
		$params['owner_id'] = $shop->id;
	}
	$num = rand();
	if (!$params['title'] || !$params['sku']) return response(false);

	$product = $productService->checkSKU($params['sku']);
	$product_data = [];
	if ($product) return response(false);
	if ($params['id']) {
		$product_data['id'] = $params['id'];
	}
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
		$product_data['category'] = implode(',', $params['category']);
	}
	$product_data['owner_id'] = $params['owner_id'];
	$product_data['type'] = 'shop';
	$product_data['title'] = $params['title'];
	$product_data['description'] = $params['description'];
	$product_data['sku'] = $params['sku'];
	$product_data['price'] = $params['price'];
	$product_data['model'] = $params['model'];
	$product_data['tag'] = $params['tag'];
	$product_data['tax'] = $params['tax'];
	$product_data['friendly_url'] = $params['friendly_url'];
	$product_data['weight'] = $params['weight'];
	$product_data['expiry_type'] = $params['expiry_type'];
	$product_data['currency'] = $params['currency'];
	$product_data['origin'] = $params['origin'];
	$product_data['product_order'] = 0;
	$product_data['duration'] = $params['duration'];
	$product_data['storage_duration'] = $params['storage_duration'];
	$product_data['is_special'] = $params['is_special'];
	$product_data['product_group'] = $params['product_group'];
	$product_data['creator_id'] = $loggedin_user->id;
	$product_data['custom_attributes'] = $params['custom_attributes'];
	$product_data['download'] = $params['download'];
	$product_data['featured'] = $params['featured'];
	$product_data['begin_day'] = $params['begin_day'];
	$product_data['end_day'] = $params['end_day'];
	$product_data['manufacturer'] = $params['manufacturer'];
	$product_data['sale_price'] = 0;
	$product_data['unit'] = $params['unit'];
	$product_data['approved'] = 0;
	$product_data['enabled'] = 0;
	$product_data['adjourn_price'] = $params['adjourn_price'];
	$product_data['adjourn_price'] = $params['adjourn_price'];
	$product_data['voucher_category'] = $params['voucher_category'];
	$product_data['ticket_category'] = $params['ticket_category'];
	$product_data['shop_category'] = $params['shop_category'];
	$product_data['market_category'] = $params['market_category'];

	$uploadedFiles = $request->getUploadedFiles();
    $images = false;
    if ($uploadedFiles) {
	    $images = $uploadedFiles['images'];
    }

    return response($productService->save($product_data, $images));
});

$app->put($container['administrator'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$categoryService = CategoryService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	// lay theo trang thai phe duyet || khong truyen approved se lay theo status
	// approved ( false la chua phe duyet || true la da phe duyet)
	if (!array_key_exists('approved', $params)) 	$params['approved'] = false;
	/* lay theo trang thai  status ( kieu status la number (0,1,2))
		khong truyen status se lay tat ca
	*/
	if (!array_key_exists('status', $params)) 		$params['status'] = "0,1";
	/* lay theo cua hang ( kieu gia tri cua shop_id la number)
		khong truyen shop_id se lay tat ca
	*/
	if (!array_key_exists('shop_id', $params)) 		$params['shop_id'] = false;
	/* loc theo loai sp ( kieu gia tri cua type_product la string )
		featured: la sp noi bat
		default: la sp binh thuong
		voucher: la sp voucher
		ticket: la sp ticket
		all: la lay tat ca
	*/
	if (!array_key_exists('type_product', $params)) 	$params['type_product'] = "all";
	/* lay theo danh muc ( kieu gia tri cua category_id la number)
		khong truyen category_id se lay tat ca
	*/
	if (!array_key_exists('category_id', $params)) 	$params['category_id'] = false;
	if (!array_key_exists('offset', $params)) 			$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 			$params['limit'] = 10;

	$approved = $params['approved'];
	$status = $params['status'];
	$shop_id = $params['shop_id'];
	$type_product = $params['type_product'];
	$category_id = $params['category_id'];
	$offset = $params['offset'];
	$limit = $params['limit'];

	$product_params[] = [
		'key' => 'id',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	if ($approved) {
		$product_params[] = [
			'key' => "approved",
			'value' => "> 0",
			'operation' => ''
		];
	} else {
		$product_params[] = [
			'key' => "approved",
			'value' => "= 0",
			'operation' => ''
		];
	}
	$product_params[] = [
		'key' => "status",
		'value' => "IN ({$status})",
		'operation' => 'AND'
	];

	if ($shop_id) {
		$product_params[] = [
			'key' => "owner_id",
			'value' => "= {$shop_id}",
			'operation' => 'AND'
		];
	}

	if ($category_id) {
		$product_params[] = [
			'key' => "FIND_IN_SET({$category_id}, category)",
			'value' => '',
			'operation' => 'AND'
		];
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
				if ($params['shop_id']) {
					$product->quantity = 0;
					$store_quantity = ProductStoreService::getInstance()->checkQuantityInStore($product->id, $loggedin_user->chain_store);
					if ($store_quantity) {
						$product->quantity = $store_quantity->quantity;
					}
				}
				if ($categories) $product->categories = $categories;
				$products[$key] = $product;
			}
		}
	}

	return response(array_values($products));
});

$app->delete($container['administrator'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) $params['id'] = false;
	$product = $productService->getProductByType($params['id'], 'id');
	if (!$product) return response(false);
	
	return response($productService->updateStatus($product->id, 2));
});