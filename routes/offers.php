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

	$offer = $select->getOffers($offer_params, 0, 1, false);
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
	$counter_offers = $select->getCounters($counter_params, $offset, $limit, true);

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
	$users = $select->getUsers($user_params,0,999999);
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
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	$time = time();

	$users = [];

	$loggedin_user = loggedin_user();
	if ($loggedin_user->usercurrency)
		$currency_code = $loggedin_user->usercurrency;

	if (!array_key_exists('location_lat', $params)) $params['location_lat'] = false;
	if (!array_key_exists('location_lng', $params)) $params['location_lng'] = false;
	if (!array_key_exists('offset', $params)) 		$params['offset'] = 0;
	if (!array_key_exists('limit', $params)) 		$params['limit'] = 10;
	if (!array_key_exists('target', $params)) 		$params['target'] = "default";
	
	$offset = $params['offset'];
	$limit = $params['limit'];

	$offer_params = null;
	$offer_params[] = [
		'key' => 'status',
		'value' => "= 'open'",
		'operation' => ''
	];
	$offer_params[] = [
		'key' => 'quantity',
		'value' => ">= 1",
		'operation' => 'AND'
	];
	$offer_params[] = [
		'key' => 'product_snapshot',
		'value' => "<> ''",
		'operation' => 'AND'
	];
	switch ($params['target']) {
		case 'public':
			return response(false);
			break;
		case 'location':
			return response(false);
			break;
		case 'friends':
			$friends_guid = getFriendsGUID($loggedin_user->guid);
			if (!$friends_guid) return response(false);
			$friends_guid = implode(',', $friends_guid);
			$offer_params[] = [
				'key' => 'owner_guid',
				'value' => "IN ({$friends_guid})",
				'operation' => 'AND'
			];
			$offer_params[] = [
				'key' => 'target',
				'value' => "= 'friends'",
				'operation' => 'AND'
			];
			$user_params = null;
			$user_params[] = [
				'key' => 'guid',
				'value' => "IN ({$friends_guid})",
				'operation' => ''
			];
			$users = $select->getUsers($user_params,0,999999999);
			
			break;
		default:
			$offer_params[] = [
				'key' => 'owner_guid',
				'value' => "= {$loggedin_user->guid}",
				'operation' => 'AND'
			];
			
			break;
	}

	$offers = $select->getOffers($offer_params, $offset, $limit);
	if (!$offers) return response(false);

	$snapshots_guid = $offers_guid = [];
	if (is_array($offers)) {
		foreach ($offers as $key => $offer) {
			if (!in_array($offer->product_snapshot, $snapshots_guid)) {
				array_push($snapshots_guid, $offer->product_snapshot);
			}
			if (!in_array($offer->guid, $offers_guid)) {
				array_push($offers_guid, $offer->guid);
			}
		}
	}

	$snapshots_guid = implode(',', $snapshots_guid);
	$snapshot_params = null;
	$snapshot_params[] = [
		'key' => 'guid',
		'value' => "IN ({$snapshots_guid})",
		'operation' => ''
	];
	$snapshots = $select->getSnapshots($snapshot_params, $offset, $limit);
	if (!$snapshots) return response(false);

	$relation_params = null;
	$relation_params[] = [
		'key' => 'relation_from',
		'value' => "= {$loggedin_user->guid}",
		'operation' => ''
	];
	$relation_params[] = [
		'key' => 'relation_to',
		'value' => "IN ({$offers_guid})",
		'operation' => 'AND'
	];
	$relation_params[] = [
		'key' => 'type',
		'value' => "= 'bookmark:offer'",
		'operation' => 'AND'
	];
	$bookmarks = $select->getRelationships($relation_params,0,9999999);

	$offers_guid = implode(',', $offers_guid);
	$counter_params = null;
	$counter_params[] = [
		'key' => 'offer_guid',
		'value' => "IN ({$offers_guid})",
		'operation' => ''
	];
	$counter_params[] = [
		'key' => 'status',
		'value' => "= 'pending'",
		'operation' => 'AND'
	];
	$counter_params[] = [
		'key' => 'offer_guid',
		'value' => "= 'count'",
		'operation' => 'count'
	];
	$counter_params[] = [
		'key' => 'offer_guid',
		'value' => "",
		'operation' => 'query_params'
	];
	$counter_params[] = [
		'key' => 'offer_guid',
		'value' => "= ''",
		'operation' => 'group_by'
	];

	$counters = $select->getCounters($counter_params, 0, 99999999999999);
	if (is_array($offers)) {
		foreach ($offers as $key => $offer) {
			if ($offer->owner_guid == $loggedin_user->guid) {
				$offer->owner = $loggedin_user;
			} else {
				if (!$users) return response(false);
				foreach ($users as $key => $user) {
					if ($offer->owner_guid == $user->guid) {
						$offer->owner = $user;
					}
				}
			}
			if (!is_object($offer->owner)) {
				unset($offers[$key]);
				continue;
			}

			if ($snapshots) {
				foreach ($snapshots as $key => $snapshot) {
					if ($snapshot->guid == $offer->product_snapshot) {
						$offer->product_snapshot = $snapshot;
					}
				}
			}

			if (!is_object($offer->product_snapshot)) {
				unset($offers[$key]);
				continue;
			}

			if ($offer->owner_guid == $loggedin_user->guid) {
				$offer->offered = true;
			}
			$offer->bookmarked = 0;
			if ($bookmarks) {
				foreach ($bookmarks as $key => $bookmark) {
					if ($bookmark->relation_to == $offer->guid) {
						$offer->bookmarked = 1;
					}
				}
			}
			$offer->counter_offers_number = 0;
			if ($counters) {
				foreach ($counters as $key => $counter) {
					if ($counter->offer_guid == $offer->guid) {
						$offer->counter_offers_number = $counter->count;
					}
				}
			}

			if ($offer->duration < 1) {
				$hour = $offer->duration*24;
				$time_end = strtotime("+{$hour} hours", $offer->time_created);
			} else {
				$time_end = strtotime("+{$offer->duration} days", $offer->time_created);
			}

			$offer->current_time = $time;
			$offer->time_end = $time_end;
			$offers[$key] = $offer;
		}
	}
	if (!$offers) return response(false);
	return response(array_values($offers));

});