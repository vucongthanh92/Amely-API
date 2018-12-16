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
			$transaction_params = $transactionService->getTransactionParams($offer->owner_id, 'user', '', '', 'offer', $offer->id, 25, $offer->owner_id);
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

					$transaction_params = $transactionService->getTransactionParams($counter->creator_id, 'user', '', '', 'counter', $counter->id, 9, $counter->creator_id);
            		$transactionService->save($transaction_params);
				}
			}

		}
	}
	return response(true);
	
})->setName('console_offers');