<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();

	if ($loggedin_user->usercurrency)
		$currency_code = $loggedin_user->usercurrency;
	
	if (!array_key_exists('offset', $params)) 		$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 		$params['limit'] = 10;
	if (!array_key_exists('offer_guid', $params)) 	$params['offer_guid'] = 0;
	
	$offset = (double)$params['offset'];
	$limit = (double)$params['limit'];
	$offer_guid = (int)$params['offer_guid'];

	if (!$offer_guid) return response(false);

	$snapshots_guid = $users_guid = [];

	$offer_params = null;
	$offer_params[] = [
		'key' => 'guid',
		'value' => "= {$offer_guid}",
		'operation' => ""
	];
	$offer_params[] = [
		'key' => 'quantity',
		'value' => ">= 1",
		'operation' => "AND"
	];
	$offer_params[] = [
		'key' => 'product_snapshot',
		'value' => "<> ''",
		'operation' => "AND"
	];

	$offer = $select->getOffers($offer_params, 0, 1);
	if (!$offer) return response(false);
	array_push($users_guid, $offer->owner_guid);
	array_push($snapshots_guid, $offer->product_snapshot);

	$counter_params = null;
	$counter_params[] = [
		'key' => 'offer_guid',
		'value' => "= {$offer_guid}",
		'operation' => ""
	];
	if ($offer->status == "approved") {
		$counter_params[] = [
			'key' => 'status',
			'value' => "= 'approved'",
			'operation' => "AND"
		];
	} else {
		$counter_params[] = [
			'key' => 'status',
			'value' => "= 'pending'",
			'operation' => "AND"
		];
	}
	$counter_offers = $select->getCounters($counter_params, $offset, $limit);

	if ($counter_offers && is_array($counter_offers)) {
		foreach ($counter_offers as $key => $counter_offer) {
			array_push($users_guid, $counter_offer->owner_guid);
			array_push($snapshots_guid, $counter_offer->product_snapshot);
		}
	}
	$users_guid = array_unique($users_guid);
	$snapshots_guid = array_unique($snapshots_guid);

	$user_params = null;
	$user_params[] = [
		'key' => 'guid',
		'value' => "IN ({$users_guid})",
		'operation' => ''
	];
	$users = $select->getUsers($user_params,0,999999,false);
	if (!$users) return response(false);

	$snapshot_params = null;
	$snapshot_params[] = [
		'key' => 'guid',
		'value' => "= ({$snapshots_guid})",
		'operation' => ''
	];
	$snapshots = $select->getSnapshots($snapshot_params,0,999999999);

	if ($counter_offers && is_array($counter_offers)) {
		foreach ($counter_offers as $key => $counter_offer) {
			if ($counter_offer->owner_guid == $loggedin_user->guid) {
				$offer->offered = true;
			}
			foreach ($users as $key => $user) {
				if ($offer->owner_guid == $user->guid) {
					$offer->owner = $user;
				}
			}
			if ($counter_offer->product_snapshot) {
				foreach ($snapshots as $snapshot) {
					if ($snapshot->guid == $counter_offer->product_snapshot) {
						$counter_offer->product_snapshot = $product_snapshot;
					}
				}
				if (!is_object($counter_offer->product_snapshot)) unset($counter_offers[$key]);
			}
		}
		$offer->counter_offers = array_values($counter_offers);
	}

	if ($offer->duration < 1) {
		$hour = $offer->duration*24;
		$time_end = strtotime("+{$hour} hours", $offer->time_created);
	} else {
		$time_end = strtotime("+{$offer->duration} days", $offer->time_created);
	}

	$offer->current_time = time();
	$offer->time_end = $time_end;
	if ($offer->owner_guid == $loggedin_user->guid) {
		$offer->offered = true;
	}

	$relation_params = null;
	$relation_params[] = [
		'key' => 'relation_from',
		'value' => "= {$loggedin_user->guid}",
		'operation' => ''
	];
	$relation_params[] = [
		'key' => 'relation_to',
		'value' => "= {$offer->guid}",
		'operation' => 'AND'
	];
	$relation_params[] = [
		'key' => 'type',
		'value' => "= 'bookmark:offer'",
		'operation' => 'AND'
	];
	$bookmark = $select->getRelationships($relation_params,0,9999999);

	$offer->bookmarked = 0;
	if ($bookmark) $offer->bookmarked = 1;
	
	return response($offer);

});

$app->post($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
	$userService = UserService::getInstance();
	$itemService = ItemService::getInstance();
	$snapshotService = SnapshotService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();

	$users = [];

	$params = $request->getParsedBody();
	$time = time();
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

$app->put($container['prefix'].'/offers', function (Request $request, Response $response, array $args) {
	$offerService = OfferService::getInstance();
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

	if (!$params['item_id'] || !$params['quantity']) return response(false);
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
	return response($offerService->save($data));
});