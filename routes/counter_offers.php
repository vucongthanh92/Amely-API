<?php
use Slim\Http\Request;
use Slim\Http\Response;


$app->get($container['prefix'].'/counter_offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$userService = UserService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$loggedin_user = loggedin_user();
	$time = time();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('counter_id', $params)) 	$params['counter_id'] = 0;
	if (!$params['counter_id']) return response(false);

	$counter = $counterService->getCounterByType($params['counter_id']);
	if ($counter->item_id) {
		$item = $itemService->getItemByType($counter->item_id);
		$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
		$item->snapshot = $snapshot;
		$counter->item = $item;
	}
	$owner = $userService->getUserByType($counter->creator_id, 'id', false);
	$counter->owner = $owner;

	$offer = $offerService->getOfferByType($counter->owner_id);
	$owner = $userService->getUserByType($offer->owner_id, 'id', false);
	$offer->owner = $owner;
	$item = $itemService->getItemByType($offer->item_id);
	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	$item->snapshot = $snapshot;
	$offer->item = $item;

	$counter->offer = $offer;
	return response($counter);
});

$app->post($container['prefix'].'/counter_offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();
	if (!$params) $params = [];
	if (!array_key_exists('offer_id', $params)) $params['offer_id'] = false;
	if (!array_key_exists('offset', $params)) 	$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 	$params['limit'] = 10;

	$counter_params = null;
	$counter_params[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => ''
	];
	if ($params['offer_id']) {
		$counter_params[] = [
			'key' => 'owner_id',
			'value' => "= {$params['offer_id']}",
			'operation' => 'AND'
		];
	} else {
		$counter_params[] = [
			'key' => 'creator_id',
			'value' => "= {$loggedin_user->id}",
			'operation' => 'AND'
		];
	}
	$counters = $counterService->getCounters($counter_params);
	if (!$counters) return response(false);
	// foreach ($counters as $key => $counter) {
	// 	if ($counter->item_id) {
	// 		$item = $itemService->getItemByType($counter->item_id, 'id');
	// 		$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	// 		$item->snapshot = $snapshot;
	// 		$counter->item = $item;
	// 	}
	// 	if ($params['offer_id']) {
	// 		$owner = $userService->getUserByType($counter->creator_id, 'id');
	// 	} else {
	// 		$owner = $loggedin_user;
	// 	}
	// 	$counter->owner = $owner;

	// 	$offer = $offerService->getOfferByType($counter->owner_id);
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
	// 	$counter_number = $counterService->getCounter($counter_params);
	// 	$offer->counter_offers_number = $counter_number->count;
	// 	$item = $itemService->getItemByType($offer->item_id, 'id');
	// 	$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id, 'id');
	// 	$item->snapshot = $snapshot;
	// 	$offer->item = $item;
	// 	if ($offer->owner_id == $loggedin_user->id) {
	// 		$owner = $loggedin_user;
	// 	} else {
	// 		$owner = $userService->getUserByType($offer->owner_id, 'id');
	// 	}
	// 	$offer->owner = $owner;
	// 	$counter->offer = $offer;
	// 	$counters[$key] = $counter;
	// }

	$offers_id = $counters_id = $items_id = $snapshots_id = $owners_id = [];
	foreach ($counters as $key => $counter) {
		if ($counter->item_id) {
			array_push($items_id, $counter->item_id);
		}
		array_push($offers_id, $counter->owner_id);
		array_push($owners_id, $counter->creator_id);
	}
	if (!$offers_id) return response(false);
	$offers_id = implode(',', array_unique($offers_id));
	$offer_params = null;
	$offer_params[] = [
		'key' => 'id',
		'value' => "IN ({$offers_id})",
		'operation' => ''
	];
	$offers = $offerService->getOffers($offer_params, 0, 99999999);

	foreach ($offers as $key => $offer) {
		if ($offer->item_id) {
			array_push($items_id, $offer->item_id);
		}
		array_push($owners_id, $offer->owner_id);
	}

	if (!$items_id) return response(false);
	$items_id = implode(',', array_unique($items_id));
	$item_params = null;
	$item_params[] = [
		'key' => 'id',
		'value' => "IN ({$items_id})",
		'operation' => ''
	];
	$items = $itemService->getItems($item_params, 0, 999999999);

	foreach ($items as $key => $item) {
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
	$snapshots = $snapshotService->getSnapshots($snapshot_params, 0, 999999999);

	if (!$owners_id) return response(false);
	$owners_id = implode(',', array_unique($owners_id));
	$owner_params = null;
	$owner_params[] = [
		'key' => 'id',
		'value' => "IN ({$owners_id})",
		'operation' => ''
	];
	$owners = $userService->getUsers($owner_params, 0, 999999999, false);

	foreach ($counters as $key => $counter) {
		foreach ($offers as $offer) {
			foreach ($owners as $owner) {
				if ($owner->id == $counter->creator_id) {
					$counter->owner = $owner;
				}
				if ($owner->id == $offer->owner_id) {
					$offer->owner = $owner;
				}
			}
			foreach ($items as $item) {
				foreach ($snapshots as $snapshot) {
					if ($item->snapshot_id == $snapshot->id) {
						$item->snapshot = $snapshot;
					}
				}
				if ($item->id == $counter->item_id) {
					$counter->item = $item;
				}
				if ($item->id == $offer->item_id) {
					$offer->item = $item;
				}
			}
			if ($offer->id == $counter->owner_id) {
				$counter->offer = $offer;
			}
		}
		$counters[$key] = $counter;
	}

	return response(array_values($counters));
});

$app->put($container['prefix'].'/counter_offers', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();
	$inventoryService = InventoryService::getInstance();
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
	if ($offer->owner_id == $loggedin_user->id) return response(false);
	if ($offer->status != 0) return response(false);
	$counter_params = null;
	$counter_params[] = [
		'key' => 'owner_id',
		'value' => "= {$offer->id}",
		'operation' => ''
	];
	$counter_params[] = [
		'key' => 'creator_id',
		'value' => "= {$loggedin_user->id}",
		'operation' => 'AND'
	];
	$counter = $counterService->getCounter($counter_params);
	if ($counter) return response(false);

	if ($offer->offer_type == 2) {
		$counter_params = null;
		$counter_params[] = [
			'key' => 'owner_id',
			'value' => "= {$offer->id}",
			'operation' => ''
		];
		$counter_params[] = [
			'key' => 'staus',
			'value' => "<> 2",
			'operation' => ''
		];
		$counter_params[] = [
			'key' => '*',
			'value' => "'count'",
			'operation' => 'count'
		];
		$counters = $counterService->getCounter($counter_params);
		if ($offer->limit_counter == $counters->count) return response(false);
	}

	if ($offer->offer_type != 2) {
		if ($params['item_id'] && $params['quantity']) {
			$item = $itemService->checkItemOfOwner($params['item_id'], $loggedin_user->id, 'user');
			if ($item->quantity < $params['quantity']) return response(false);
			if (!$item) return response(false);
			$params['item_id'] = $itemService->separateItem($params['item_id'], $params['quantity']);
			$itemService->updateStatus($params['item_id'], 0);
		}
	}

	$status = 0;
	switch ($offer->offer_type) {
		case 0:
			$status = 0;
			break;
		case 1:
			$status = 0;
			break;
		case 2:
			if ($offer->option == 1) {
				$status = 1;
			} else {
				$status = 0;
			}
			break;
		default:
			$status = 0;
			break;
	}

	$counter_data['owner_id'] = $params['offer_id'];
	$counter_data['creator_id'] = $loggedin_user->id;
	$counter_data['item_id'] = $params['item_id'];
	$counter_data['status'] = $status;

	if ($counterService->save($counter_data)) {
		if ($offer->offer_type == 2) {
			if ($status == 1) {
				$item = $itemService->getItemByType($offer->item_id, 'id');
				if (!$item) return response(false);
				if ($item->status != 0) return response(false);
				if ($item->quantity == 1) {
					$item_id = $item->id;
					$offerService->updateStatus($offer->id, 2);
				} else {
					$item_id = $itemService->separateItem($offer->item_id, 1);
				}
				$itemService->changeOwnerItem($loggedin_user->id, 'user', $item_id);
				return response(true);
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
					if ($counter->item_id) {
						$itemService->changeOwnerItem($counter->creator_id, 'user', $counter->item_id);
					}
					$counterService->updateStatus($counter->id, 1);
				}
				$offerService->updateStatus($offer->id, 1);
			}
		}
		return response(true);
	}
	return response(false);
});

$app->delete($container['prefix'].'/counter_offers', function (Request $request, Response $response, array $args) {
	$transactionService = TransactionService::getInstance();
	$notificationService = NotificationService::getInstance();
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('counter_id', $params)) 	$params['counter_id'] = 0;
	$counter = $counterService->getCounterByType($params['counter_id']);
	$offer = $offerService->getOfferByType($counter->owner_id, 'id');
	if ($offer->owner_id != $loggedin_user->id && $counter->creator_id != $loggedin_user->id) return response(false);
	if ($offer->owner_id == $loggedin_user->id) {
		$counterService->updateStatus($counter->id, 2);
		$transaction_params = $transactionService->getTransactionParams($loggedin_user->id, 'user', '', '', 'counter', $counter_id, 6, $loggedin_user->id);
        $transactionService->save($transaction_params);

	} else if ($counter->creator_id == $loggedin_user->id) {
		$counterService->updateStatus($counter->id, 3);
	}
	if ($counter->item_id) {
		$itemService->updateStatus($counter->item_id, 1);
	}
	return response(true);
});