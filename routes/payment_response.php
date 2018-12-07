<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/payment_response', function (Request $request, Response $response, array $args) {
	
	$transactionService = TransactionService::getInstance();
	$shippingService = ShippingService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();
	$paymentsService = PaymentsService::getInstance();
	$itemService = ItemService::getInstance();
	$walletService = WalletService::getInstance();
	$params = $request->getQueryParams();

	if (!$params) $params = [];
	if (!array_key_exists('payment_id', $params)) return redirectURL($error);

	$payment = $paymentsService->getPaymentById($params['payment_id']);
	switch ($payment->status) {
		case 0:
			$pm = $paymentsService->getMethod($payment->payment_method);
			$pm->order_id = $payment->owner_id;
			$pm->order_type = $payment->type;
			$pm->payment_id = $payment->id;
			$response = $pm->getResult();
			if (!$response) return redirectURL($error);
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
							$transaction_params['status'] = 11;
							break;
						case 1:
							$paymentsService->processOrder($response['order_id'], $response['order_type']);
							$transaction_params['status'] = 12;
							break;
						case 2:
							$transaction_params['status'] = 13;
							break;
						default:
							# code...
							break;
					}
					response($transactionService->save($transaction_params));
					return redirectURL(1);
					break;
				case 'WALLET':
					
					$options = unserialize($payment->options);
					$total = $options['total'];
					$owner_id = $options['creator_id'];
					switch ($options['action']) {
						case 'RENEW':
							$duration = $options['duration'];
							$item_id = $options['item_id'];
							$itemService->renew($item_id, $duration);
							$walletService->deposit($owner_id, $total, 16, $owner_id, "wallet");
							$walletService->withdraw($owner_id, $total, 19, $item_id, "item");
							return redirectURL(1);
							break;
						case 'DELIVERY_ITEM':
							$shipping_method = $options['shipping_method'];
							$item_id = $itemService->separateItem($options['item_id'], $options['quantity']);
							$sm = $shippingService->getMethod($shipping_method);
							$sm->item_id = $item_id;
							$sm->shipping_info = $options;
							$do_id = $sm->redeemDelivery();

							$deliveryOrder = new DeliveryOrder();
							$deliveryOrder->data->item_id = $item_id;
							$deliveryOrder->data->id = $do_id;
							$deliveryOrder->where = "id = {$do_id}";
							$deliveryOrder->update(true);

							$walletService->deposit($owner_id, $total, 16, $owner_id, "wallet");
							$walletService->withdraw($owner_id, $total, 22, $do_id, "do");
							return redirectURL(1);
							break;
						default:
							return redirectURL(0);
							break;
					}
					break;
				default:
					# code...
					break;
			}
			break;
		case 1:
			return redirectURL(1);
			break;
		case 2:
			return redirectURL(0);
			break;
		default:
			return redirectURL(0);
			break;
	}

	
	

})->setName('payment_response');