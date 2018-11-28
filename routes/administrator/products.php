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
	$product_data['images'] = $params['images'];

	return response($productService->save($product_data));
});