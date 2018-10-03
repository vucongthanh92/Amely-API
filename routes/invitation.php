<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('offset', $params))  	$params['offset'] = 0;
	if (!array_key_exists('limit', $params))  	$params['limit'] = 10;

	$relationships = $relationshipService->getRelationsByType(false, $loggedin_user->id, 'friend:request');
	if (!$relationships) return response(false);
	foreach ($relationships as $key => $relationship) {
		if ($relationshipService->getRelationByType($relationship->relation_to, $relationship->relation_from, 'friend:request')) unset($relationships[$key]);
	}

	if (!$relationships) return response(false);
	$users_id = array_map(create_function('$o', 'return $o->relation_from;'), array_values($relationships));
	$users_id = implode(',', $users_id);
	$users = $userService->getUsersByType($users_id, 'id', false);

	return response($users);
});

$app->put($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('from_id', $params))  	$params['from_id'] = $loggedin_user->id;
	if (!array_key_exists('to_id', $params))  		$params['to_id'] = false;
	if (!array_key_exists('type', $params))  		$params['type'] = false;

	if (!$params['from_id'] || !$params['to_id'] || !$params['type']) return response(false);
	$from = $params['from_id'];
	$tos = $params['to_id'];
	$type = $params['type'];

	switch ($type) {
		case 'user':
			$from = $loggedin_user->id;
			foreach ($tos as $key => $to) {
				if ($relationshipService->getRelationByType($from, $to, 'friend:request')) {
					if ($relationshipService->getRelationByType($to, $from, 'friend:request')) return response(false);
					$relationship = new Relationship;
					$relationship->data->relation_from = $to;
					$relationship->data->relation_to = $from;
					$relationship->data->type = 'friend:request';
					return response($relationship->insert());
				}
				$relationship = new Relationship;
				$relationship->data->relation_from = $from;
				$relationship->data->relation_to = $to;
				$relationship->data->type = 'friend:request';
				return response($relationship->insert());
			}
			break;
		case 'group':
			foreach ($tos as $key => $to) {
				if (!$relationshipService->getRelationByType($from, $to, 'group:approve')) {
					$relationship = new Relationship;
					$relationship->data->relation_from = $to;
					$relationship->data->relation_to = $from;
					$relationship->data->type = "group:invite";
					$relationship->insert();

					$relationship = new Relationship;
					$relationship->data->relation_from = $from;
					$relationship->data->relation_to = $to;
					$relationship->data->type = "group:approve";
					$relationship->insert();
				}

				continue;
			}
			return response(true);
			break;
		case 'event':
			# code...
			break;
		default:
			# code...
			break;
	}
});

$app->delete($container['prefix'].'/invitation', function (Request $request, Response $response, array $args) {

});