<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/console_promotions', function (Request $request, Response $response, array $args) {
	$promotionService = PromotionService::getInstance();
	$promotionItemService = PromotionItemService::getInstance();
	$productService = ProductService::getInstance();
	$time = time();

	$conditions = null;
	$conditions[] = [
		'key' => 'start_time',
		'value' => "< {$time}",
		'operation' => ''
	];
	$conditions[] = [
		'key' => 'end_time',
		'value' => "> {$time}",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => 'approved',
		'value' => "= 0",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => 'status',
		'value' => "= 1",
		'operation' => 'AND'
	];

	$promotions = $promotionService->getPromotions($conditions, 0, 99999999);
	if ($promotions) {
		foreach ($promotions as $key => $promotion) {
			$promotionService->approved($promotion->id);
		}
	}

	$conditions = null;
	$conditions[] = [
		'key' => 'end_time',
		'value' => "<= {$time}",
		'operation' => ''
	];
	$conditions[] = [
		'key' => 'approved',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => 'status',
		'value' => "= 1",
		'operation' => 'AND'
	];

	$promotions = $promotionService->getPromotions($conditions, 0, 99999999);
	if ($promotions) {
		foreach ($promotions as $key => $promotion) {
			$promotionService->updateStatus($promotion->id, 2);
		}
	}

	$promotions_runing = $promotionService->getPromotionsRuning();

	$conditions = null;
	$conditions[] = [
		'key' => 'approved',
		'value' => "> 0",
		'operation' => 'AND'
	];
	$conditions[] = [
		'key' => 'status',
		'value' => "= 1",
		'operation' => 'AND'
	];
	if ($promotions_runing) {
		$promotions_runing_id = array_unique(array_map(create_function('$o', 'return $o->id;'), $promotions_runing));
		$promotions_runing_id = implode(',', $promotions_runing_id);
		$conditions[] = [
			'key' => 'id',
			'value' => "NOT IN ({$promotions_runing_id})",
			'operation' => 'AND'
		];	
	}
	$promotions = $promotionService->getPromotions($conditions, 0, 99999999);
	if ($promotions) {
		foreach ($promotions as $key => $promotion) {
			$promotion_items = $promotionItemService->getPromotionItemsByPromotionId($promotion->id, 0, 99999999);
            if ($promotion_items) {
                foreach ($promotion_items as $promotion_item) {
                    $productService->generateSnapshotSalePrice($promotion_item->product_id, 0);
                }
            }
		}
	}
	
	return response(true);
})->setName('console_promotions');
