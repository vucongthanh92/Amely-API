<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin he thong
$app->get($container['administrator'].'/approval', function (Request $request, Response $response, array $args) {
	// $siteSettingService = SiteSettingService::getInstance();
	// $loggedin_user = loggedin_user();
	// $settings = $siteSettingService->getSiteSettings(null, 0, 99999999);
	// return response($settings);
});

// them hoac chinh sua thong tin he thong
$app->post($container['administrator'].'/approval', function (Request $request, Response $response, array $args) {
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
			if (is_array($params['subject_id'])) {
				foreach ($params['subject_id'] as $subject_id) {
					$shop = $shopService->getShopByType($subject_id, 'id', false);
					if ($shop) {
						$store = $storeService->getStoreByType($shop->id, 'owner_id', false);
						if (!$store) return responseError("store_erro");
						if ($shopService->approval($subject_id)) {
							$user = $userService->getUserByType($shop->owner_id, 'id', false);
							if ($user->type != 'admin') {
								$user = object_cast("User", $user);
								$user->data->type = 'manager';
								$user->data->id = $user->id;
								$user->data->chain_store = $store->id;
								$user->where = "id = {$user->id}";
								$user->update();
							}
						}
					}
				}
				return response(true);
			}
			break;
		case 'store':
			if (is_array($params['subject_id'])) {
				foreach ($params['subject_id'] as $subject_id) {
					$storeService->approval($subject_id);
				}
				return response(true);
			}
			break;
		case 'product':
			if (is_array($params['subject_id'])) {
				foreach ($params['subject_id'] as $subject_id) {
					$productService->approval($subject_id);
				}
				return response(true);
			}
			break;
		case 'advertise':
			if (is_array($params['subject_id'])) {
				foreach ($params['subject_id'] as $subject_id) {
					$advertiseService->approval($subject_id);
				}
				return response(true);
			}
			break;
		default:
			# code...
			break;
	}
	return response(false);
});