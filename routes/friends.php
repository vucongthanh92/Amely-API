<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/friends', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$relationshipService = RelationshipService::getInstance();

    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();

    if (array_key_exists("user_id", $params) && is_numeric($params['user_id'])) {
    	$user_params = null;
    	$user_params[] = [
    		'key' => 'id',
    		'value' => "= {$params['user_id']}",
    		'operation' => ''
    	];
        $user = $userService->getUser($user_params,false);
    } else {
        $user = $loggedin_user;
    }
    if (!$user) return response(false);

    $friends_id = $relationshipService->getFriendsGUID($user->id);
    if (!$friends_id) return response(false);
    $friends_id = implode(",", array_unique($friends_id));
	if ($user->id != $loggedin_user->id) {
		$friends_requested_id = $relationshipService->getFriendRequested($loggedin_user->id, $friends_id);
	}

	$friends = $userService->getUsersByType($friends_id, 'id', 0, 99999999, false);
	foreach ($friends as $key => $friend) {
		if ($friend->id != $loggedin_user->id) {
            if ($user->id != $loggedin_user->id) {
                if ($friends_requested_id) {
    			    if (in_array($friend->id, $friends_requested_id)) {
    	            	$friend->requested = 1;
    			    }
                }
            }
		}
	}
	$friends = array_values($friends);
    if (!$friends) return response(false);
	return response($friends);
});

$app->delete($container['prefix'].'/friends', function (Request $request, Response $response, array $args) {
    $relationshipService = RelationshipService::getInstance();
    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();
    if (!array_key_exists('user_id', $params)) $params['user_id'] = false;

    if (!$params['user_id']) return response(false);
    if (!is_numeric($params['user_id'])) return response(false);
    if ($loggedin_user->id == $params['user_id']) return response(false);

    return response($relationshipService->deleteFriend($loggedin_user->id, $params['user_id']));
});