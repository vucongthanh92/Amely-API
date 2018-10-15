<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$userService = UserService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();

	$loggedin_user = loggedin_user();
	$time = time();
	if (!array_key_exists('offer_id', $params)) 	$params['offer_id'] = 0;
	if (!$params['offer_id']) return response(false);
	
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];

	$offer = $offerService->getOfferByType($params['offer_id'], 'id');
	if (!$offer) return response(false);

	$item = $itemService->getItemByType($offer->item_id);
	if (!$item) return response(false);
	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');

	$offer->snapshot = $snapshot;


	if ($offer->duration < 1) {
		$hour = $offer->duration*24;
		$time_end = strtotime("+{$hour} hours", $offer->time_created);
	} else {
		$time_end = strtotime("+{$offer->duration} days", $offer->time_created);
	}

	$offer->current_time = time();
	$offer->time_end = $time_end;
	if ($offer->owner_id == $loggedin_user->id) {
		$offer->offered = true;
	}
	
	return response($offer);
});

$app->post($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$userService = UserService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();

	$loggedin_user = loggedin_user();
	$time = time();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('offset', $params)) 		$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 		$params['limit'] = 10;
	if (!array_key_exists('target', $params)) 		$params['target'] = 0;
	if (!array_key_exists('friends', $params)) 		$params['friends'] = false;

	
	$offset = $params['offset'];
	$limit = $params['limit'];

	$offer_params = null;
	$offer_params[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => ''
	];
	$offer_params[] = [
		'key' => 'target',
		'value' => "= {$params['target']}",
		'operation' => 'AND'
	];
	$offer_params[] = [
		'key' => 'owner_id',
		'value' => "<> {$loggedin_user->id}",
		'operation' => 'AND'
	];
	switch ($params['target']) {
		case 0:
			break;
		case 2:
			break;
		case 1:
			if ($params['friends']) {
				$friends_id = implode(',', array_unique($params['friends']));
				$offer_params[] = [
					'key' => 'owner_id',
					'value' => "IN ({$friends_id})",
					'operation' => 'AND'
				];
			}
			
			break;
		default:
			
			
			break;
	}

	$offers = $offerService->getOffers($offer_params, $offset, $limit);
	if (!$offers) return response(false);

	foreach ($offers as $key => $offer) {
		$owner = $userService->getUserByType($offer->owner_id, 'id', false);
		$offer->owner = $owner;
		$item = $itemService->getItemByType($offer->item_id, 'id');
		$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
		$offer->snapshot = $snapshot;


			// if ($offer->duration < 1) {
			// 	$hour = $offer->duration*24;
			// 	$time_end = strtotime("+{$hour} hours", $offer->time_created);
			// } else {
			// 	$time_end = strtotime("+{$offer->duration} days", $offer->time_created);
			// }

			// $offer->current_time = $time;
			// $offer->time_end = $time_end;
			// $offers[$key] = $offer;

	}

	return response(array_values($offers));

});

$app->patch($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();
	if (!$params) $params = [];
	if (!array_key_exists('offer_id', $params)) $params['offer_id'] = false;
	if (!array_key_exists('counter_id', $params)) $params['counter_id'] = false;

	if (!$params['offer_id'] || !$params['counter_id']) return response(false);
	$offer = $offerService->getOfferByType($params['offer_id'], 'id');
	$counter = $counterService->getCounterByType($params['counter_id'], 'id');
	
	$item = new Item();
	$item->data->owner_id = $counter->creator_id;
	$item->data->status = 1;
	$item->where = "id = {$offer->item_id}";
	$item->update();

	$item = new Item();
	$item->data->owner_id = $offer->owner_id;
	$item->data->status = 1;
	$item->where = "id = {$counter->item_id}";
	$item->update();

	$offer = object_cast("Offer", $offer);
	$offer->data->status = 1;
	$offer->where = "id = {$offer->id}";
	$offer->update();

	$counter = object_cast("Counter", $counter);
	$counter->data->status = 1;
	$counter->where = "id = {$counter->id}";
	$counter->update();

	$counter_params = null;
	$counter_params[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => ''
	];
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => "= {$offer->id}",
		'operation' => 'AND'
	];
	$counters = $counterService->getCounters($counter_params, 0, 99999999);
	if ($counters) {
		foreach ($counters as $key => $counter) {
			$item = new Item();
			$item->data->owner_id = $counter->creator_id;
			$item->data->status = 1;
			$item->where = "id = {$counter->item_id}";
			$item->update();
		}
	}
	return response(true);

});

$app->put($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('type', $params)) $params['type'] = 'user';
	if (!array_key_exists('target', $params)) $params['target'] = 0;
	if (!array_key_exists('duration', $params)) $params['duration'] = 0;
	if (!array_key_exists('offer_type', $params)) $params['offer_type'] = 0;
	if (!array_key_exists('status', $params)) $params['status'] = 0;
	if (!array_key_exists('option', $params)) $params['option'] = 0;
	if (!array_key_exists('limit_counter', $params)) $params['limit_counter'] = 1;
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	if (!array_key_exists('note', $params)) $params['note'] = "";
	if (!array_key_exists('quantity', $params)) $params['quantity'] = 0;
	if (!$params['item_id'] || !$params['quantity'] || !$params['duration']) return response(false);
	switch ($params['offer_type']) {
		case 0:
			# code...
			break;
		case 1:
			# code...
			break;
		case 2:
			if ($params['option']) {
				$params['limit_counter'] = 10;
			} else {
				$params['limit_counter'] = $params['quantity'];
			}
			break;
		
		default:
			# code...
			break;
	}
	$item = $itemService->getItemByType($params['item_id']);
	if ($item->owner_id != $loggedin_user->id) return response(false);
	$item_id = $itemService->separateItem($params['item_id'], $params['quantity']);

	$data = [];
	$data['owner_id'] = $loggedin_user->id;
	$data['target'] = $params['target'];
	$data['duration'] = $params['duration'];
	$data['offer_type'] = $params['offer_type'];
	$data['limit_counter'] = $params['limit_counter'];
	$data['item_id'] = $item_id;
	$data['note'] = $params['note'];
	$data['option'] = $params['option'];
	$offer_id = $offerService->save($data);
	if ($offer_id) {
		if ($params['offer_type'] == 1) {
			$counter_params = null;
			$counter_params['offer_id'] = $offer_id;
			$counter_params['item_id'] = $item_id;
			$counter_params['creator_id'] = $loggedin_user->id;
			$counter_params['status'] = 0;
			$counter_id = $counterService->save($counter_params);
			return response($counter_id);
		}
		return response(true);
	}
	return response(false);
});