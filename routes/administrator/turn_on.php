<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['administrator'].'/turn_on', function (Request $request, Response $response, array $args) {
	$transactionService = TransactionService::getInstance();
	$shopService = ShopService::getInstance();
	$storeService = StoreService::getInstance();
	$productService = ProductService::getInstance();
	$userService = UserService::getInstance();
	$advertiseService = AdvertiseService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	// subject_id array number
	if (!array_key_exists('subject_id', $params)) 	return response(false);
	if (!array_key_exists('subject_type', $params)) return response(false);

	$transaction_data = null;
	$transaction_data['subject_type'] = $params['subject_type'];

	switch ($params['subject_type']) {
		case 'shop':
			
			break;
		case 'store':
			// if (is_array($params['subject_id'])) {
			// 	foreach ($params['subject_id'] as $subject_id) {
			// 		$storeService->approval($subject_id);
			// 	}
			// 	return response(true);
			// }
			break;
		case 'product':
			if (is_array($params['subject_id'])) {
				foreach ($params['subject_id'] as $subject_id) {
					$productService->updateStatus($subject_id, 1);
				}
				return response(true);
			}
			break;
		case 'advertise':
			// if (is_array($params['subject_id'])) {
			// 	foreach ($params['subject_id'] as $subject_id) {
			// 		$advertiseService->approval($subject_id);
			// 	}
			// 	return response(true);
			// }
			break;
		default:
			# code...
			break;
	}
	return response(false);
});