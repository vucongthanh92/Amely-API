<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/counter_offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();
	if (!$params) $params = [];
	if (!array_key_exists('offer_id', $params)) $params['offer_id'] = false;
	if (!array_key_exists('item_id', $params)) $params['item_id'] = 0;
	if (!array_key_exists('quantity', $params)) $params['quantity'] = 0;
	if (!array_key_exists('note', $params)) $params['note'] = "";

	if (!$params['offer_id']) return response(false);

	$offer = $offerService->getOfferByType($params['offer_id']);
	if ($offer->status != 0) return response(false);
	if ($offer->limit_counter != 0) return response(false);
	$counter_params = null;
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => "= {$offer->id}",
		'operation' => ''
	];
	$counter_params[] = [
		'key' => '*',
		'value' => "'count'",
		'operation' => 'count'
	];
	$counters = $counterService->getCounter($counter_params);
	$count = $counters->count;
	if ($offer->limit_counter == $count) return response(false);


	if ($params['item_id'] && $params['quantity']) {
		$params['item_id'] = $itemService->separateItem($params['item_id'], $params['quantity']);
	}

	$data = [];
	$data['offer_id'] = $params['offer_id'];
	$data['item_id'] = $params['item_id'];
	$data['creator_id'] = $loggedin_user->id;

	return response($counterService->save($data));
});