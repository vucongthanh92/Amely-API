<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/share', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$feedService = FeedService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('type', $params)) $params['type'] = 'feed';
	if (!array_key_exists('subject_id', $params)) $params['subject_id'] = false;
	
	$type = $params['type'];
	$subject_id = $params['subject_id'];
	
	switch ($type) {
		case 'feed':
			$conditions = null;
	        $conditions[] = [
	            'key' => 'id',
	            'value' => "= {$subject_id}",
	            'operation' => ''
	        ];
	        $feed = $feedService->getFeed($conditions, false);
	        $item_type = 'feed';
	        $item_id = $subject_id;
			if ($feed->item_type) $item_type = $feed->item_type;
			if ($feed->item_id) $item_id = $feed->item_id;

			$share = new Feed();
			$share->data->owner_id 		= $feed->owner_id;
			$share->data->type 			= $feed->type;
			$share->data->title 		= $feed->title;
			$share->data->description 	= $feed->description;
			$share->data->location 		= $feed->location;
			$share->data->tag 			= $feed->tag;
			$share->data->mood_id 		= $feed->mood_id;
			$share->data->poster_id 	= $loggedin_user->id;
			$share->data->privacy 		= $feed->privacy;
			$share->data->images 		= $feed->images;
			$share->data->item_type 	= $item_type;
			$share->data->item_id 		= $item_id;
			$id = $share->insert(true);
			if ($id) {
				global $settings;
				$source = $settings['image']['path']."/feed/{$feed->id}";
				$dest = $settings['image']['path']."/feed/{$id}";
				$services->recurse_copy($source, $dest);
				return response(true);
			}

			break;
		case 'product':
			return response(false);
			break;
		default:
			return response(false);
			break;
	}
});