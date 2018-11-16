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

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('offer_id', $params)) 	$params['offer_id'] = 0;
	if (!$params['offer_id']) return response(false);

	$offer = $offerService->getOfferByType($params['offer_id'], 'id');
	if (!$offer) return response(false);

	$item = $itemService->getItemByType($offer->item_id);
	if (!$item) return response(false);
	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	$item->snapshot = $snapshot;
	$offer->item = $item;

	return response($offer);
});

$app->post($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
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
	
	switch ($params['target']) {
		case 0:
			$offer_params[] = [
				'key' => 'owner_id',
				'value' => "= {$loggedin_user->id}",
				'operation' => 'AND'
			];
			break;
		case 2:
			if (!array_key_exists('offers_id', $params)) return false;
			$offers_id = implode(',', array_unique($params['offers_id']));
			$offer_params[] = [
				'key' => 'id',
				'value' => "IN ({$offers_id})",
				'operation' => 'AND'
			];

			break;
		case 1:
			$offer_params[] = [
				'key' => 'target',
				'value' => "= 1",
				'operation' => 'AND'
			];
			if ($params['friends']) {
				$friends_id = implode(',', array_unique($params['friends']));
				$offer_params[] = [
					'key' => 'owner_id',
					'value' => "IN ({$friends_id})",
					'operation' => 'AND'
				];

				$offer_params[] = [
					'key' => 'owner_id',
					'value' => "<> {$loggedin_user->id}",
					'operation' => 'AND'
				];
			}
			break;
		default:
			break;
	}
	if (!$offer_params) return response(false);
	$offers = $offerService->getOffers($offer_params, $offset, $limit);
	if (!$offers) return response(false);

	// foreach ($offers as $key => $offer) {
	// 	$owner = $userService->getUserByType($offer->owner_id, 'id', false);
	// 	$offer->owner = $owner;
	// 	$item = $itemService->getItemByType($offer->item_id, 'id');
	// 	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	// 	$item->snapshot = $snapshot;
	// 	$offer->item= $item;

	// 	$counter_params = null;
	// 	$counter_params[] = [
	// 		'key' => '*',
	// 		'value' => "count",
	// 		'operation' => 'count'
	// 	];
	// 	$counter_params[] = [
	// 		'key' => 'owner_id',
	// 		'value' => "= {$offer->id}",
	// 		'operation' => ''
	// 	];
	// 	$counter_params[] = [
	// 		'key' => 'status',
	// 		'value' => "= 0",
	// 		'operation' => 'AND'
	// 	];
	// 	$counters = $counterService->getCounter($counter_params);
	// 	$offer->counter_offers_number = $counters->count;
	// 	$offers[$key] = $offer;
	// }

	$offers_id = $owners_id = $items_id = $snapshots_id = [];
	foreach ($offers as $key => $offer) {
		array_push($offers_id, $offer->id);
		array_push($owners_id, $offer->owner_id);
		array_push($items_id, $offer->item_id);
	}

	if (!$owners_id) return response(false);
	$owners_id = implode(',', array_unique($owners_id));
	$owner_params = null;
	$owner_params[] = [
		'key' => 'id',
		'value' => "IN ({$owners_id})",
		'operation' => ''
	];
	$owners = $userService->getUsers($owner_params, 0, 99999999);
	if (!$owners) return response(false);

	if (!$items_id) return response(false);
	$items_id = implode(',', array_unique($items_id));
	$item_params = null;
	$item_params[] = [
		'key' => 'id',
		'value' => "IN ({$items_id})",
		'operation' => ''
	];
	$items = $itemService->getItems($item_params, 0, 99999999);
	if (!$items) return response(false);

	foreach ($items as $item) {
		array_push($snapshots_id, $item->snapshot_id);
	}

	if (!$snapshots_id) return response(false);
	$snapshots_id = implode(',', array_unique($snapshots_id));
	$snapshot_params = null;
	$snapshot_params[] = [
		'key' => 'id',
		'value' => "IN ({$snapshots_id})",
		'operation' => ''
	];
	$snapshots = $snapshotService->getSnapshots($snapshot_params, 0, 99999999);
	if (!$snapshots) return response(false);

	if (!$offers_id) return response(false);
	$offers_id = implode(',', array_unique($offers_id));

	$counter_params = null;
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => '',
		'operation' => 'query_params'
	];

	$counter_params[] = [
		'key' => 'count (*) as `count` ',
		'value' => '',
		'operation' => 'query_params'
	];
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => "IN ({$offers_id})",
		'operation' => ''
	];
	$counter_params[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => 'AND'
	];
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => '',
		'operation' => 'group_by'
	];

	$counters = $counterService->getCounters($counter_params, 0, 99999999);

	foreach ($offers as $key => $offer) {
		if ($counters) {
			foreach ($counters as $counter) {
				if ($counter->owner_id == $offer->id) {
					$offer->counter_offers_number = $counter->count;
				}
			}
		} else {
			$offer->counter_offers_number = 0;
		}
		foreach ($owners as $owner) {
			if ($offer->owner_id == $owner->id) {
				$offer->owner = $owner;
			}
		}
		foreach ($items as $item) {
			foreach ($snapshots as $snapshot) {
				if ($item->snapshot_id == $snapshot->id) {
					$item->snapshot = $snapshot;
				}
			}
			if ($item->id == $offer->item_id) {
				$offer->item = $item;
			}
		}
		$offers[$key] = $offer;
	}

	return response(array_values($offers));
});

$app->patch($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$transactionService = TransactionService::getInstance();
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
	if ($offer->owner_id != $loggedin_user->id) return response(false);
	$counter_params = null;
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => "= {$offer->id}",
		'operation' => ''
	];
	$counter_params[] = [
		'key' => 'id',
		'value' => "= {$params['counter_id']}",
		'operation' => 'AND'
	];
	$counter = $counterService->getCounter($counter_params);
	if (!$counter) return response(false);
	$itemService->changeOwnerItem($counter->creator_id, 'user', $offer->item_id);
	$itemService->changeOwnerItem($offer->owner_id, 'user', $counter->item_id);

	$offerService->updateStatus($offer->id, 1, $counter->id);
	$counterService->updateStatus($counter->id, 1);

	$noty_params = null;
	$noty_params['offer_id'] = $offer->id;
	$noty_params['counter_id'] = $counter->id;
	$notificationService->save($noty_params, 'counter:accept');

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
			$itemService->changeOwnerItem($counter->creator_id, 'user', $counter->item_id);
			$counterService->updateStatus($counter->id, 2);
		}
	}
	return response(true);
});

$app->put($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();
	$inventoryService = InventoryService::getInstance();
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
			$params['option'] = 0;
			break;
		case 1:
			$params['option'] = 0;
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
	if (!$item) return response(false);
	$inventory_params = null;
	$inventory_params[] = [
		'key' => 'id',
		'value' => "= {$item->owner_id}",
		'operation' => ''
	];
	$inventory = $inventoryService->getInventory($inventory_params);
	if ($inventory->type != 'user') return response(false);
	if ($item->owner_id != $inventory->id) return response(false);
	$item_id = $itemService->separateItem($params['item_id'], $params['quantity']);

	$offer_data = null;
	$offer_data['owner_id'] = $loggedin_user->id;
	$offer_data['type'] = 'user';
	$offer_data['title'] = "";
	$offer_data['description'] = "";
	$offer_data['target'] = $params['target'];
	$offer_data['duration'] = $params['duration'];
	$offer_data['offer_type'] = $params['offer_type'];
	$offer_data['option'] = $params['option'];
	$offer_data['limit_counter'] = $params['limit_counter'];
	$offer_data['item_id'] = $item_id;
	$offer_data['note'] = $params['note'];

	$offer_id = $offerService->save($offer_data);
	if ($offer_id) {
		if ($params['offer_type'] == 1) {
			$counter_data = null;
			$counter_data['offer_id'] = $offer_id;
			$counter_data['item_id'] = $item_id;
			$counter_data['creator_id'] = $loggedin_user->id;
			$counter_data['status'] = 0;
			$counter_id = $counterService->save($counter_data);
			if ($counter_id) return response($offer_id);
			return response(false);
		}
		return response($offer_id);
	}
	return response(false);
});

$app->delete($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$userService = UserService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$loggedin_user = loggedin_user();
	$time = time();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('offer_id', $params)) 	$params['offer_id'] = 0;
	
	$offer = $offerService->getOfferByType($params['offer_id']);
	if (!$offer) return response(false);
	if ($offer->status != 0) return response(false);
	if ($offer->owner_id != $loggedin_user->id) return response(false);
	$offerService->updateStatus($offer->id, 2);
	$itemService->changeOwnerItem($offer->owner_id, 'user', $offer->item_id);
	$counter_params = null;
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => "= {$params['offer_id']}",
		'operation' => ''
	];
	$counters = $counterService->getCounters($counter_params, 0, 99999999);
	if ($counters) {
		foreach ($counters as $key => $counter) {
			if ($counter->item_id) {
				$counterService->updateStatus($counter->id, 2);
				$itemService->changeOwnerItem($counter->creator_id, 'user', $counter->item_id);
			}
		}
	}

	$transactionService = TransactionService::getInstance();
	$transaction_params['owner_id'] = $offer->owner_id;
	$transaction_params['type'] = 'user';
	$transaction_params['title'] = "";
	$transaction_params['description'] = "";
	$transaction_params['subject_type'] = 'offer';
	$transaction_params['subject_id'] = $offer->id;
	$transaction_params['status'] = 4;
	$transactionService->save($transaction_params);
	return response(true);
});