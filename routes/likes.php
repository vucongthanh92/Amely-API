<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/likes', function (Request $request, Response $response, array $args) {
	$notificationService = NotificationService::getInstance();
	$likeService = LikeService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);

	$from = $loggedin_user->id;
	$subject_id = $params['subject_id'];
	$to = $subject_id;
	$type = $params['type'];
	if (!in_array($type, ['feed', 'business', 'shop'])) return response(false);
	if ($likeService->isLiked($from, $to, $type)) return response(false);
	switch ($params['type']) {
		case 'feed':
			$feedService = FeedService::getInstance();
			$feed = $feedService->getFeedByType($params['subject_id'], 'id');
			if (!$feed) return response(false);
			$userService = UserService::getInstance();
			$user = $userService->getUserByType($feed->poster_id, 'id');
			break;
		
		default:
			# code...
			break;
	}

	$data = null;
	$data['owner_id'] = $subject_id;
	$data['type'] = $type;
	$data['owner'] = $user;
	$data['creator'] = $loggedin_user;

	if ($likeService->save($data)) {
		$notificationService = NotificationService::getInstance();
		$notify_params = null;
		switch ($data['type']) {
			case 'feed':
				$notification_type = "like:feed";
				$notify_params['from'] = $loggedin_user;
				$notify_params['to'] = $user;
				$notify_params['subject_id']  = $data['owner_id'];
				break;
			case 'shop':
				$shopService = ShopService::getInstance();
				$shop = $shopService->getShopByType($data['owner_id'], 'id');
				$owner = getInfo($shop->owner_id, 'user');
				$notify_params['from'] = $loggedin_user;
				$notify_params['to'] = $owner;
				$notify_params['subject_id']  = $data['owner_id'];
				$notification_type = "like:shop";
				break;
			case 'product':
				$notify_params['product_id']  = $data['owner_id'];
				$notification_type = "like:product";
				break;
			default:
				return false;
				break;
		}
		$notificationService->save($notify_params, $notification_type);
		return response(true);
	}
	return response(false);
});

$app->delete($container['prefix'].'/likes', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('subject_id', $params)) return response(false);
	if (!array_key_exists('type', $params)) return response(false);

	$from = $loggedin_user->id;
	$subject_id = $params['subject_id'];
	$type = $params['type'];
	if (!in_array($type, ['feed', 'business', 'shop'])) return response(false);

	$like = new Like();
	$like->where = "creator_id = {$loggedin_user->id} AND owner_id = {$subject_id} AND type ='{$type}'";
	return response($like->delete());

});