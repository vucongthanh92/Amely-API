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

			$offers = [];
			foreach ($relations as $key => $relation) {
				$offer = $offerService->getOfferByType($relation->relation_to);
				$item = $itemService->getItemByType($offer->item_id);
				$snapshot = $snapshotService->getSnapshotByType($item->snapshot_id);
				$item->snapshot = $snapshot;
				$offer->item = $item;
				array_push($offers, $offer);
			}
			return ["offers" => $offers];
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