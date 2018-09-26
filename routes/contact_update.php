<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->put($container['prefix'].'/contact_update', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$userService = UserService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('mobiles', $params)) return response(false);
	$mobiles = $params['mobiles'];
	$mobiles = array_unique($mobiles);

	if (is_array($mobiles)) {
		foreach ($mobiles as $key => $mobile) {
			$mobile = preg_replace("/^\\+?84/i", "0", $mobile);
			$user = $userService->getUserByType($mobile, 'mobilelogin', false);
			if (!$user) {
				unset($mobiles[$key]);
				continue;
			}
			if ($loggedin_user->id != $user->id) {
				if (!$relationshipService->getRelationByType($loggedin_user->id, $user->id, 'friend:request')) {
					$relationship = new Relationship;
					$relationship->data->relation_from = $loggedin_user->id;
					$relationship->data->relation_to = $user->id;
					$relationship->data->type = 'friend:request';
					$relationship->insert();
				}
			}
		}
	}
	return response(true);

});