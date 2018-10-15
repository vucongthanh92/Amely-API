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
	if ($offer->limit_counter == $counters->count) return response(false);

	if ($params['item_id'] && $params['quantity']) {
		$params['item_id'] = $itemService->separateItem($params['item_id'], $params['quantity']);
	}
	$status = 0;
	switch ($params['offer_type']) {
		case 0:
			$status = 0;
			break;
		case 1:
			$status = 0;
			break;
		case 2:
			if ($offer->option) {
				$status = 1;
			} else {
				$status = 0;
			}
			break;
		default:
			$status = 0;
			break;
	}

	$data = [];
	$data['offer_id'] = $params['offer_id'];
	$data['item_id'] = $params['item_id'];
	$data['creator_id'] = $loggedin_user->id;
	$data['status'] = $status;

	if ($counterService->save($data)) {
		if ($offer->offer_type == 2) {
			if ($status == 1) {
				$item_id = $itemService->separateItem($offer->item_id, 1);
				$item = $itemService->getItemByType($item_id, 'id');
				$item = object_cast("Item", $item);
				$item->data->owner_id = $loggedin_user->id;
				$item->where = "id = {$item->id}";
				return response($item->update());
			}
		}
		if ($offer->offer_type == 1) {
			$counter_params = null;
			$counter_params[] = [
				'key' => 'owner_id',
				'value' => "= {$offer->id}",
				'operation' => ''
			];
			$counter_params[] = [
				'key' => 'status',
				'value' => "= 0",
				'operation' => 'AND'
			];
			$counters = $counterService->getCounters($counter_params, 0, 99999999);

			if ($offer->limit_counter == count($counters)) {
				$counters = joiner_shuffle($counters);
				foreach ($counters as $key => $counter) {
					$item = new Item();
					$item->data->owner_id = $counter->creator_id;
					$item->data->status = 1;
					$item->where = "id = {$counter->item_id}";
					$item->update();

					$counter_offer = new Counter();
					$counter_offer->data->status = 1;
					$counter_offer->where = "id = {$counter->id}";
					$counter_offer->update();
				}
				$offer = object_cast("Offer", $offer);
				$offer->data->status = 1;
				$offer->update();
			}
		}
		return response(true);
	}
	return response(false);
});