<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/friends', function (Request $request, Response $response, array $args) {
	$userService = UserService::getInstance();
	$relationshipService = RelationshipService::getInstance();

    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();

    if (array_key_exists("user_guid", $params) && is_numeric($params['user_guid'])) {
    	$user_params = null;
    	$user_params[] = [
    		'key' => 'guid',
    		'value' => "= {$params['user_guid']}",
    		'operation' => ''
    	];
        $user = $userService->getUser($user_params,false);
    } else {
        $user = $loggedin_user;
    }
    if (!$user) return response(false);

    $friends_guid = $relationshipService->getFriendsGUID($loggedin_user->guid);
    $friends_guid = implode(",", array_unique($friends_guid));
	if ($user->guid != $loggedin_user->guid) {
		$friends_requested_guid = $relationshipService->getFriendRequested($loggedin_user->guid, $friends_guid);
	}

	$user_params = null;
	$user_params[] = [
		'key' => 'guid',
		'value' => "IN ({$friends_guid})",
		'operation' => ''
	];
	$friends = $userService->getUsers($user_params,0,99999999,false);
	foreach ($friends as $key => $friend) {
		if ($friend->guid != $loggedin_user->guid) {
            if ($user->guid != $loggedin_user->guid) {
                if ($friends_requested_guid) {
    			    if (in_array($friend->guid, $friends_requested_guid)) {
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
    if (!array_key_exists('user_guid', $params)) $params['user_guid'] = false;

    if (!$params['user_guid']) return response(false);
    if (!is_numeric($params['user_guid'])) return response(false);

    return response($relationshipService->deleteFriend($loggedin_user->id, $params['user_guid']));
});