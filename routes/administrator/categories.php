<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 danh muc
$app->get($container['administrator'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$shopService = ShopService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) return response(false);

	$category = $categoryService->getCategoryByType($params['id'], 'id');

	if ($category->type == 'shop') {
		$shop = $shopService->getShopByType($category->owner_id, 'id', true);
	}
	$creator = $userService->getUserByType($category->creator_id, 'id', false);

	$category->shop = $shop;
	$category->creator = $creator;

	return response($category);
});

// them hoac chinh sua danh muc
$app->post($container['administrator'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) $params['id'] = false;
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = 0;
	if (!array_key_exists('type', $params)) $params['type'] = 0;
	if (!array_key_exists('title', $params)) $params['title'] = 0;
	if (!array_key_exists('description', $params)) $params['description'] = 0;
	if (!array_key_exists('subtype', $params)) $params['subtype'] = 0;
	if (!array_key_exists('friendly_url', $params)) $params['friendly_url'] = "";
	if (!array_key_exists('sort_order', $params)) $params['sort_order'] = 0;
	if (!array_key_exists('enabled', $params)) $params['enabled'] = 1;
	if (!array_key_exists('parent_id', $params)) $params['parent_id'] = 0;
	if (!array_key_exists('creator_id', $params)) $params['creator_id'] = $loggedin_user->id;

	$category_data = null;
	if ($params['id']) {
		$category_data['id'] = $params['id'];	
	}
	$category_data['owner_id'] = $params['owner_id'];
	$category_data['type'] = $params['type'];
	$category_data['title'] = $params['title'];
	$category_data['description'] = $params['description'];
	$category_data['parent_id'] = 0;
	if ($params['subtype'] == 0 || $params['subtype'] == 3) {
		$category_data['parent_id'] = $params['parent_id'];
	}
	if (!$category_data['parent_id']) {
		$category_data['parent_id'] = null;
	}
	$category_data['subtype'] = $params['subtype'];
	$category_data['friendly_url'] = time();
	$category_data['sort_order'] = $params['sort_order'];
	if ($params['sort_order'] == "undefined") {
		$category_data['sort_order'] = 0;
	}

	$category_data['enabled'] = $params['enabled'];
	if ($params['enabled'] == "undefined") {
		$category_data['enabled'] = 0;
	}
	
	$category_data['creator_id'] = $loggedin_user->id;
	
	$uploadedFiles = $request->getUploadedFiles();
    $logo = false;
    if ($uploadedFiles) {
	    $uploadedFile = $uploadedFiles['logo'];
	    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
	        $files = $request->getUploadedFiles();
	        $logo = $files['logo'];
	    }
    }
	return response($categoryService->save($category_data, $logo));
});

// thong tin nhieu danh muc
$app->put($container['administrator'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;
	if (!array_key_exists('keyword', $params)) 		$params['keyword'] = false;
	if (!array_key_exists('type', $params)) $params['type'] = 0;
	if (!array_key_exists('enabled', $params)) $params['enabled'] = -1;

	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];
	$shop_id = $params['shop_id'];
	$type = $params['type'];

	$category_params[] = [
		'key' => 'CAST(`sort_order` AS SIGNED)',
		'value' => "ASC",
		'operation' => 'order_by'
	];

	if ($shop_id) {
		$category_params[] = [
			'key' => 'owner_id',
			'value' => "= {$shop_id}",
			'operation' => ''
		];
		$category_params[] = [
			'key' => 'type',
			'value' => "= 'shop'",
			'operation' => 'AND'
		];
	} else {
		$category_params[] = [
			'key' => 'subtype',
			'value' => "= {$params['type']}",
			'operation' => ''
		];
	}
	if ($params['enabled'] >= 0) {
		$category_params[] = [
			'key' => 'enabled',
			'value' => "= {$params['enabled']}",
			'operation' => 'AND'
		];	
	}
	if ($params['keyword']) {
		$category_params[] = [
			'key' => 'AND title',
			'value' => "'%".$params['keyword']."%'",
			'operation' => 'LIKE'
		];
	}

	$categories = $categoryService->getCategories($category_params, $offset, $limit);
	if (!$categories) return response(false);
	return response($categories);
});

// xoa danh muc
$app->delete($container['administrator'].'/categories', function (Request $request, Response $response, array $args) {
	$categoryService = CategoryService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) return response(false);
	$category = $categoryService->getCategoryByType($params['id'], 'id');
	$category = object_cast("Category", $category);
	$category->data->id = $category->id;
	$category->where = "id = {$category->id}";

	if ($loggedin_user->type == 'admin') {
		return response($category->delete(true));
	}

	if ($loggedin_user->id == $category->creator_id && $category->type == 'shop') {
		return response($category->delete(true));
	}

	return response(false);
});

