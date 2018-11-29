<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/payment_response', function (Request $request, Response $response, array $args) {
	$transactionService = TransactionService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();
	$paymentsService = PaymentsService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('payment_id', $params)) return response(false);

	$payment = $paymentsService->getPaymentById($params['payment_id']);
	switch ($payment->status) {
		case 0:
			$pm = $paymentsService->getMethod($payment->payment_method);
			$pm->order_id = $payment->owner_id;
			$pm->order_type = $payment->type;
			$pm->payment_id = $payment->id;
			$response = $pm->getResult();
			if (!$response) return response(false);
			switch ($payment->type) {
				case 'HD':
					$po = $purchaseOrderService->getPOByType($response['order_id'], 'id');
					$transaction_params['owner_id'] = $po->owner_id;
					$transaction_params['type'] = 'user';
					$transaction_params['title'] = "";
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'order';
					$transaction_params['subject_id'] = $po->id;
					switch ($response['status']) {
						case 0:
							die('0');
							$transaction_params['status'] = 11;
							break;
						case 1:
							$paymentsService->processOrder($response['order_id'], $response['order_type']);
							die('131254');
							$transaction_params['status'] = 12;
							break;
						case 2:
							die('2');
							$transaction_params['status'] = 13;
							break;
						default:
							# code...
							break;
					}
					return response($transactionService->save($transaction_params));
					break;
				case 'ITEM':
					$itemService = ItemService::getInstance();
					$options = unserialize($payment->options);
					$duration = $options['duration'];
					$creator_id = $options['creator_id'];
					$amount = $options['amount'];
					$itemService->renew($payment->owner_id, $duration);

					$transaction_params['owner_id'] = $creator_id;
					$transaction_params['type'] = 'wallet';
					$transaction_params['title'] = $amount;
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'wallet';
					$transaction_params['subject_id'] = $creator_id;
					$transaction_params['status'] = 16;
					$transactionService->save($transaction_params);

					$transaction_params['owner_id'] = $creator_id;
					$transaction_params['type'] = 'wallet';
					$transaction_params['title'] = $amount;
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'item';
					$transaction_params['subject_id'] = $payment->owner_id;
					$transaction_params['status'] = 19;
					$transactionService->save($transaction_params);
					return response(true);

					break;
				default:
					# code...
					break;
			}
			break;
		case 1:
			die('gion mat voi t ha');
			return response(true);
			break;
		case 2:
			return response(false);
			break;
		default:
			return response(false);
			break;
	}

	
	

})->setName('payment_response');