<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/delivery_orders', function (Request $request, Response $response, array $args) {
	$deliveryOrderService = DeliveryOrderService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('type', $params)) $params['type'] = 'user';
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;

	switch ($params['type']) {
		case 'user':
			$dos = $deliveryOrderService->getDOsByUser($params['owner_id'], $params['offset'], $params['limit']);
			break;
		case 'store':
			$dos = $deliveryOrderService->getDOsByStore($params['owner_id'], $params['offset'], $params['limit']);
			break;
		case 'so':
			$dos = $deliveryOrderService->getDOsBySO($params['owner_id'], $params['offset'], $params['limit']);
			break;
		default:
			return response(false);
			break;
	}
	return response($dos);
});

$app->put($container['prefix'].'/delivery_orders', function (Request $request, Response $response, array $args) {
	$itemService = ItemService::getInstance();
	$storeService = StoreService::getInstance();
	$snapshotService = SnapshotService::getInstance();
	$addressService = AddressService::getInstance();
	$notificationService = NotificationService::getInstance();
	$purchaseOrderService = PurchaseOrderService::getInstance();
	$paymentsService = PaymentsService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('payment_method', $params)) $params['payment_method'] = false;
	if (!array_key_exists('shipping_fullname', $params)) $params['shipping_fullname'] = false;
	if (!array_key_exists('shipping_phone', $params)) $params['shipping_phone'] = false;
	if (!array_key_exists('shipping_address', $params)) $params['shipping_address'] = false;
	if (!array_key_exists('shipping_province', $params)) $params['shipping_province'] = false;
	if (!array_key_exists('shipping_district', $params)) $params['shipping_district'] = false;
	if (!array_key_exists('shipping_ward', $params)) $params['shipping_ward'] = false;
	if (!array_key_exists('shipping_note', $params)) $params['shipping_note'] = false;
	if (!array_key_exists('shipping_method', $params)) $params['shipping_method'] = false;
	if (!array_key_exists('shipping_fee', $params)) $params['shipping_fee'] = false;
	if (!array_key_exists('item_id', $params)) $params['item_id'] = false;
	if (!array_key_exists('quantity', $params)) $params['quantity'] = false;

	if ($params['shipping_fee'] <= 0) return response(false);

	$item = $itemService->getItemByType($params['item_id']);
	if ($item->status != 1) return response(false);
	$params['total'] = $params['shipping_fee'];
	$params['action'] = "DELIVERY_ITEM";
	$params['creator_id'] = $loggedin_user->id;
	$pm = $paymentsService->getMethod($params['payment_method']);
	$pm->options = $params;
	$pm->order_id = $loggedin_user->id;
	$pm->amount = $params['shipping_fee'];
	$pm->creator = $loggedin_user;
	$pm->order_type = "WALLET";
	$pm->payment_method = $params['payment_method'];
	$url = $pm->process();
	if (!$url) return response(false);
	return response(["url" => $url]);
});