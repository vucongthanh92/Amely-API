<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/removal', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('from_id', $params))  	$params['from_id'] = $loggedin_user->id;
	if (!array_key_exists('to_id', $params))  		$params['to_id'] = false;
	if (!array_key_exists('type', $params))  		$params['type'] = false;

	if (!$params['from_id'] || !$params['to_id'] || !$params['type']) return response(false);
	$from = $params['from_id'];
	$to = $params['to_id'];
	$type = $params['type'];

	switch ($type) {
		case 'user':
			// $from = $loggedin_user->id;
			// if ($relationshipService->getRelationByType($from, $to, 'friend:request')) {
			// 	if ($relationshipService->getRelationByType($to, $from, 'friend:request')) return response(false);
			// 	$relationship = new Relationship;
			// 	$relationship->data->relation_from = $to;
			// 	$relationship->data->relation_to = $from;
			// 	$relationship->data->type = 'friend:request';
			// 	return response($relationship->insert());
			// }
			// $relationship = new Relationship;
			// $relationship->data->relation_from = $from;
			// $relationship->data->relation_to = $to;
			// $relationship->data->type = 'friend:request';
			// return response($relationship->insert());
			break;
		case 'group':
			$userService = UserService::getInstance();
			$groupService = GroupService::getInstance();
		
			$user = $userService->getUserByType($to, 'id');
			$group = $groupService->getGroupByType($from, 'id');
			if (!$group) return response(false);
			$member = $groupService->checkMember($group->id, $to);
			if ($member) {
				if ($relationshipService->deleteMemberGroup($to, $from)) {
					return response($services->memberGroupFB($group->id, $user->username, 'delete'));
				}
			}
			return response(false);
			break;
		case 'event':
			# code...
			break;
		default:
			# code...
			break;
	}
});

$app->delete($container['prefix'].'/removal', function (Request $request, Response $response, array $args) {

});