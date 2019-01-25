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
	if (!array_key_exists('owner_id', $params)) return responseError("shop_not_empty");
	if (!array_key_exists('type', $params)) $params['type'] = 'shop';
	if (!array_key_exists('title', $params)) $params['title'] = 0;
	if (!array_key_exists('description', $params)) $params['description'] = 0;
	if (!array_key_exists('sku', $params)) return responseError("sku_not_empty");
	if (!array_key_exists('price', $params)) return responseError("price_not_empty");
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
	if (!array_key_exists('status', $params)) $params['status'] = 0;
	if (!array_key_exists('total_images', $params)) $params['total_images'] = 0;

	if (empty($params['sku'])) return responseError("sku_not_empty");
	if (!$params['owner_id']) {
		$shop = $shopService->getShopByType($loggedin_user->id, 'owner_id', false);
		if (!$shop) return response(false);
		$params['owner_id'] = $shop->id;
	}
	$num = rand();
	if (!$params['title'] || !$params['sku']) return response(false);

	$product_data = [];
	if ($params['id']) {
		$product_data['id'] = $params['id'];
		$product = $productService->getProductByType($params['id'], 'id');
		if ($product->sku != $params['sku']) {
			$check_sku = $productService->checkSKUshop($params['sku'], $params['owner_id']);
			if ($check_sku) return response([
					'status' => false,
					'error' => "sku_exist"
				]);
		}
	} else {
		$productService->checkSKUshop($params['sku'], $params['owner_id']);
		if ($check_sku) return response([
					'status' => false,
					'error' => "sku_exist"
				]);
	}
	if ($params['tag']) {
		$params['tag'] = $params['tag'];
	}
	if ($params['images']) {
		$params['images'] = $params['images'];
	}
	if ($params['begin_day']) {
		$params['begin_day'] = strtotime($params['begin_day']);
	}
	if ($params['end_day']) {
		$params['end_day'] = strtotime($params['end_day']);
	}
	if ($params['category']) {
		$product_data['category'] = $params['category'];
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
	$product_data['status'] = $params['status'];

	$uploadedFiles = $request->getUploadedFiles();
    $images = [];
    if (($uploadedFiles) && ($params['total_images'] > 0)) {
    	for($i = 0; $i < $params['total_images']; $i++) {
    		if ($uploadedFiles['image_'.($i)]) {
    			array_push($images, $uploadedFiles['image_'.$i]);
    		}
    	}
    }

    return response($productService->save($product_data, $images));
});

$app->put($container['administrator'].'/products', function (Request $request, Response $response, array $args) {
	$productService = ProductService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$categoryService = CategoryService::getInstance();
	$promotionService = PromotionService::getInstance();
	$promotionItemService = PromotionItemService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	/* lay theo trang thai phe duyet || khong truyen approved se lay theo status
	approved ( false la chua phe duyet || true la da phe duyet)
		-1 lay tat ca
		0 sp chua duyet
		1 sp duyet
	*/
	if (!array_key_exists('approved', $params)) 	$params['approved'] = -1;
	/* lay theo trang thai  status ( kieu status la number (0,1,2))
		khong truyen status se lay tat ca
	*/
	if (!array_key_exists('status', $params)) 		$params['status'] = -1;
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
	if (!array_key_exists('keyword', $params)) 		$params['keyword'] = false;
	if (!array_key_exists('offset', $params)) 			$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 			$params['limit'] = 10;

	if (!array_key_exists('isPromotion', $params)) 		$params['isPromotion'] = false;

	$approved = $params['approved'];
	$status = $params['status'];
	$shop_id = $params['shop_id'];
	$type_product = $params['type_product'];
	$category_id = $params['category_id'];
	$offset = $params['offset'];
	$limit = $params['limit'];

	$product_params[] = [
		'key' => 'p.id',
		'value' => 'DESC',
		'operation' => 'order_by'
	];
	$product_params[] = [
		'key' => "time_created",
		'value' => ">= 0",
		'operation' => ''
	];
	switch ($params['approved']) {
		case 0:
			$product_params[] = [
				'key' => "approved",
				'value' => "= 0",
				'operation' => 'AND'
			];
			break;
		case 1:
			$product_params[] = [
				'key' => "approved",
				'value' => "> 0",
				'operation' => 'AND'
			];
			break;
		default:
			# code...
			break;
	}
	
	if ($params['status'] >= 0) {
		$product_params[] = [
			'key' => "status",
			'value' => "= {$params['status']}",
			'operation' => 'AND'
		];
	} else {
		$product_params[] = [
			'key' => "status",
			'value' => "IN (0,1)",
			'operation' => 'AND'
		];
	}

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

	if ($params['keyword']) {
		$product_params[] = [
			'key' => 'AND title',
			'value' => "'%".htmlspecialchars($params['keyword'], ENT_QUOTES)."%'",
			'operation' => 'LIKE'
		];
		$product_params[] = [
			'key' => 'OR sku',
			'value' => "'%".htmlspecialchars($params['keyword'], ENT_QUOTES)."%'",
			'operation' => 'LIKE'
		];
	}

	if ($params['isPromotion']) {
		$promotion_params[] = [
			'key' => 'status',
			'value' => 'IN (0,1)',
			'operation' => ''
		];
		$promotions = $promotionService->getPromotions($promotion_params, 0, 999999999);
		if ($promotions) {
			$promotions_id = array_map(create_function('$o', 'return $o->id;'), $promotions);
			$promotions_id = implode(',', $promotions_id);
			$promotion_item_params[] = [
				'key' => 'owner_id',
				'value' => "IN ({$promotions_id})",
				'operation' => ''
			];
			$promotion_items = $promotionItemService->getPromotionItems($promotion_item_params, 0, 9999999999);
			$products_id = array_map(create_function('$o', 'return $o->product_id;'), $promotion_items);
			$products_id = implode(',', $products_id);
			$product_params[] = [
				'key' => 'id',
				'value' => "NOT IN ({$products_id})",
				'operation' => 'AND'
			];
		}
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
				$chain_store = null;
				if ($params['shop_id']) {
					$chain_store = $loggedin_user->chain_store;
				}
				$store_quantity = ProductStoreService::getInstance()->checkQuantityInStore($product->id, $chain_store);
				if ($store_quantity) {
					$product->quantity = $store_quantity->quantity;
				} else {
					$product->quantity = 0;	
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
	$services = Services::getInstance();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) return responseError(ERROR_1);
	if (!array_key_exists('image', $params)) $params['image'] = false;

	foreach ($params['id'] as $key => $id) {
		$properties = $productService->getPropertyProductByType($id, 'id');
		$p = $productService->getProductByType($id, 'id');
		if (!$properties) return response(false);
		if ($params['image']) {
			$images = [];
			$images = explode(',', $properties->images);
			$images = array_diff($images, [$params['image']]);
			
			if ($images) {
				$images = implode(',', $images);
			} else {
				$images = "";
			}
			
			$product = new Product();
			$product->data->images = $images;
			$product->data->id = $id;
			$product->where = "id = {$id}";
			$product->update(true);

		} else {
			$services->elasticsearch($p, 'product', 'delete');

			$product = new Product();
			$product->data->id = $id;
			$product->where = "id = {$id}";
			$product->delete(true);

			$productStore = new ProductStore();
			$productStore->where = "product_id = {$id}";
			$productStore->delete();
		}
	}
	return response(true);
});