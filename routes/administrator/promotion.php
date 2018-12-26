<?php
use Slim\Http\Request;
use Slim\Http\Response;

// xem chi tiet quang cao
$app->get($container['administrator'].'/promotion', function (Request $request, Response $response, array $args) {
	$promotionService = PromotionService::getInstance();
	$promotionItemService = PromotionItemService::getInstance();
	$productService = ProductService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('promotion_id', $params)) return responseError(ERROR_0);

	$promotion = $promotionService->getPromotionById($params['promotion_id']);

	$promotion_items = $promotionItemService->getPromotionItemsByPromotionId($promotion->id, 0, 9999999);

	foreach ($promotion_items as $key => $promotion_item) {
		$product = $productService->getProductByType($promotion_item->product_id, 'id');
		$promotion_item->product = $product;
		$promotion_items[$key] = $promotion_item;
	}
	$promotion->items = $promotion_items;

	return response($promotion);
});

$app->post($container['administrator'].'/promotion', function (Request $request, Response $response, array $args) {

	$promotionService = PromotionService::getInstance();
	$promotionItemService = PromotionItemService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('promotion_id', $params)) $params['promotion_id'] = false;
	if (!array_key_exists('shop_id', $params))  return responseError(ERROR_0);
	if (!array_key_exists('title', $params)) $params['title'] = "";
	if (!array_key_exists('time_type', $params)) $params['time_type'] = 0;
	if (!array_key_exists('start_time', $params)) $params['start_time'] = 0;
	if (!array_key_exists('end_time', $params)) $params['end_time'] = 0;
	/*
		percent hoac price ()
		[
			'promotion_item_id' => null,
			'product_id' => 1,
			'percent' => 10,
			'price' => null
		],
		[
			'promotion_item_id' => null,
			'product_id' => 2,
			'percent' => null,
			'price' => 2000
		]
	*/
	if (!array_key_exists('items', $params)) $params['items'] = [];

	if ($params['start_time'] > $params['end_time']) return response(false);
	if ($params['promotion_id']) {
		$promotion_data['id'] = $params['promotion_id'];	
	}
	$promotion_data['owner_id'] = $params['shop_id'];
    $promotion_data['title'] = $params['title'];
    $promotion_data['time_type'] = $params['time_type'];
    $promotion_data['start_time'] = $params['start_time'];
    $promotion_data['end_time'] = $params['end_time'];
    $promotion_data['status'] = 0;
    $promotion_data['approved'] = 0;

    $promotion_id = $promotionService->save($promotion_data);
    if (!$promotion_id) return response(false);
    if ($params['items']) {
	    foreach ($params['items'] as $key => $item) {
	    	if ($item['promotion_item_id']) {
	    		$promotionItem['id'] = $item['promotion_item_id'];
	    	}
	    	$promotionItem['owner_id'] = $promotion_id;
	    	$promotionItem['product_id'] = $item['product_id'];
	    	$promotionItem['percent'] = $item['percent'];
	    	$promotionItem['price'] = $item['price'];
	    	$promotionItemService->save($promotionItem);
	    }
    }

    return response(true);
});

$app->put($container['administrator'].'/promotion', function (Request $request, Response $response, array $args) {
	$promotionService = PromotionService::getInstance();
	$promotionItemService = PromotionItemService::getInstance();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('shop_id', $params)) $params['shop_id'] = false;
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	
	$conditions = null;
	$conditions[] = [
		'key' => 'status',
		'value' => "<> 2",
		'operation' => ''
	];

	if ($params['shop_id']) {
		$conditions[] = [
			'key' => 'owner_id',
			'value' => "= {$params['shop_id']}",
			'operation' => 'AND'
		];
	}
	$promotions = $promotionService->getPromotions($conditions, $params['offset'], $params['limit']);

	if (!$promotions) return response(false);
	return response($promotions);
});

$app->delete($container['administrator'].'/promotion', function (Request $request, Response $response, array $args) {

	$promotionService = PromotionService::getInstance();
	$promotionItemService = PromotionItemService::getInstance();
	$productService = ProductService::getInstance();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('promotion_id', $params)) return responseError(ERROR_0);
	if (!array_key_exists('promotion_item_id', $params)) $params['promotion_item_id'] = false;

	$promotion = $promotionService->getPromotionById($params['promotion_id']);

	if ($params['promotion_item_id']) {
		$promotion_item = $promotionItemService->getPromotionItemById($params['promotion_item_id']);
		if (!$promotion_item) return response(false);
		$product_id = $promotion_item->product_id;
		$promotion_item = object_cast("PromotionItem", $promotion_item);
		$promotion_item->data->id = $promotion_item->id;
		$promotion_item->where = "id = {$promotion_item->id}";
		if ($promotion_item->delete(true)) {
			$productService->generateSnapshotSalePrice($product_id, 0);
			return response(true);
		}
		return response(false);
	} else {
		$promotion_items = $promotionItemService->getPromotionItemsByPromotionId($promotion->id, 0, 9999999);

		if ($promotion_items) {
			foreach ($promotion_items as $key => $promotion_item) {
				$product_id = $promotion_item->product_id;
				$promotion_item = object_cast("PromotionItem", $promotion_item);
				$promotion_item->data->id = $promotion_item->id;
				$promotion_item->where = "id = {$promotion_item->id}";
				if ($promotion_item->delete(true)) {
					$productService->generateSnapshotSalePrice($product_id, 0);
				}
			}
		}

		$promotion = object_cast("Promotion", $promotion);
		$promotion->data->id = $promotion->id;
		$promotion->where = "id = {$promotion->id}";
		return response($promotion->delete(true));
	}

	return response(false);

});