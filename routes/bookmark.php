<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/bookmark', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params)) $params['type'] = false;
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	if (!$params['type']) return response(false);

	$relations = $relationshipService->getRelationsByType($loggedin_user->id, false, $params['type'], $params['offset'], $params['limit']);
	if (!$relations) return response(false);

	switch ($params['type']) {
		case 'offer':
			$offerService = OfferService::getInstance();
			$itemService = ItemService::getInstance();
			$snapshotService = SnapshotService::getInstance();

			$owners_id = $offers_id = $items_id = $snapshots_id = $offers = [];
			foreach ($relations as $key => $relation) {
				array_push($offers_id, $relation->relation_to);
			}
			if (!$offers_id) return response(false);
			$offers_id = implode(',', $offers_id);
			$offer_params = null;
			$offer_params[] = [
				'key' => 'id',
				'value' => "IN ({$offers_id})",
				'operation' => ''
			];
			$offers = $offerService->getOffers($offer_params, 0, 9999999);
			if (!$offers) return response(false);

			foreach ($offers as $key => $offer) {
				array_push($items_id, $offer->item_id);
				if ($offer->type == 'user') {
					array_push($owners_id, $offer->owners_id);
				}
			}

			if (!$items_id) return response(false);
			$items_id = implode(',', $items_id);
			$item_params = null;
			$item_params[] = [
				'key' => 'id',
				'value' => "IN ({$items_id})",
				'operation' => ''
			];
			$items = $itemService->getItems($item_params, 0, 9999999);
			if (!$items) return response(false);

			foreach ($items as $key => $item) {
				array_push($snapshots_id, $item->snapshot_id);
			}

			$snapshots_id = implode(',', $snapshots_id);
			$snapshot_params = null;
			$snapshot_params[] = [
				'key' => 'id',
				'value' => "IN ({$snapshots_id})",
				'operation' => ''
			];
			$snapshots = $snapshotService->getSnapshots($snapshot_params, 0, 9999999);
			if (!$snapshots) return response(false);

			if (!$owners_id) return response(false);
			$owners_id = implode(',', $owners_id);
			$owner_params = null;
			$owner_params[] = [
				'key' => 'id',
				'value' => "IN ({$owners_id})",
				'operation' => ''
			];
			$owners = $snapshotService->getSnapshots($owner_params, 0, 9999999);
			if (!$owners) return response(false);

			foreach ($offers as $key => $offer) {
				foreach ($items as $item) {
					foreach ($snapshots as $snapshot) {
						if ($item->snapshot_id == $snapshot->id) {
							$item->snapshot = $snapshot;
						}
					}
					if ($offer->item_id == $item->id) {
						$offer->item = $item;
					}
				}
				foreach ($owners as $key => $owner) {
					$offer->owner = $owner;
				}

				$offers[$key] = $offer;
			}
			return response(["offers" => array_values($offers)]);
			break;
		case 'gift':
			# code...
			break;
		default:
			# code...
			break;
	}
	return response(false);
});

$app->put($container['prefix'].'/bookmark', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) $params['subject_id'] = false;
	if (!array_key_exists('type', $params)) $params['type'] = false;

	if (!$params['subject_id'] || !$params['type']) return response(false);

	$data = [];
	$data['relation_from'] = $loggedin_user->id;
	$data['relation_to'] = $params['subject_id'];

	switch ($params['type']) {
		case 'offer':
			$offerService = OfferService::getInstance();
			$offer = $offerService->getOfferByType($params['subject_id']);
			if ($offer->status != 0) return response(false);
			$data['type'] = 'offer';
			break;
		case 'gift':
			$giftService = GiftService::getInstance();
			$gift = $giftService->getGiftByType($params['subject_id']);
			if ($gift->status != 0) return response(false);
			$data['type'] = 'gift';
			break;
		default:
			# code...
			break;
	}

	return response($relationshipService->save($data));
});

$app->delete($container['prefix'].'/bookmark', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params)) 	$params['type'] = false;

	if ($params['type'] != 'offer' || $params['type'] != 'gift') return response(false);

	$relations = $relationshipService->getRelationsByType($loggedin_user->id, false, $params['type'], 0, 99999999);

	foreach ($relations as $key => $relation) {
		$relation = object_cast("Relation", $relation);
		$relation->where = "id = {$relation->id}";
		$relation->delete();
	}
	return response(true);
});