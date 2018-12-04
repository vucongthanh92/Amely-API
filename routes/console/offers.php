<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/console_offers', function (Request $request, Response $response, array $args) {
	$transactionService = TransactionService::getInstance();
	$offerService = OfferService::getInstance();
	$counterService = CounterService::getInstance();
	$itemService = ItemService::getInstance();

	$time = time();

	$offer_params[] = [
		'key' => 'status',
		'value' => "= 0",
		'operation' => ''
	];
	$offer_params[] = [
		'key' => 'expried',
		'value' => "< {$time}",
		'operation' => 'AND'
	];

	$offers = $offerService->getOffers($offer_params, 0, 99999999);
	if (!$offers) return response(false);

	foreach ($offers as $key => $offer) {
		$offer = object_cast("Offer", $offer);
		$offer->data->status = 2;
		$offer->data->id = $offer->id;
		$offer->where = "id = {$offer->id}";
		if ($offer->update()) {
			$transaction_params = null;
			$transaction_params['status'] = 25;
			$transaction_params['subject_type'] = 'offer';
			$transaction_params['subject_id'] = $offer->id;
			$transaction_params['owner_id'] = $offer->owner_id;
			$transaction_params['type'] = 'user';
			$transaction_params['title'] = "";
			$transaction_params['description'] = "";
			$transactionService->save($transaction_params);

			$itemService->changeOwnerItem($offer->owner_id, 'user', $offer->item_id);
			$counter_params = null;
			$counter_params[] = [
				'key' => 'owner_id',
				'value' => "= {$offer->id}",
				'operation' => ''
			];
			$counters = $counterService->getCounters($counter_params, 0, 99999999);
			if ($counters) {
				foreach ($counters as $key => $counter) {
					if ($counter->item_id) {
						$counterService->updateStatus($counter->id, 2);
						$itemService->changeOwnerItem($counter->creator_id, 'user', $counter->item_id);
					}
					$transaction_params = null;
					$transaction_params['status'] = 9;
					$transaction_params['owner_id'] = $counter->creator_id;
					$transaction_params['type'] = 'user';
					$transaction_params['title'] = "";
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'counter';
					$transaction_params['subject_id'] = $counter->id;
					$transactionService->save($transaction_params);
				}
			}

		}
	}
	return response(true);
	
});