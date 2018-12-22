<?php
use Slim\Http\Request;
use Slim\Http\Response;

// thong tin chi tiet 1 user
$app->get($container['administrator'].'/permission', function (Request $request, Response $response, array $args) {

	$permissionService = PermissionService::getInstance();
	$params = $request->getQueryParams();
	if (!$params) $params = [];
	if (!array_key_exists('rule_id', $params)) return responseError(ERROR_0);
	$rule = $permissionService->getRuleByType($params['rule_id']);
	if (!$rule) return responseError(ERROR_0);
	$rule_permissions = $permissionService->getPermissionsByRule($rule->id);
	if ($rule_permissions) {
		$rule->permissions = $rule_permissions;
	}
	return response($rule);
});

$app->put($container['administrator'].'/permission', function (Request $request, Response $response, array $args) {
	$permissionService = PermissionService::getInstance();

	$rules = $permissionService->getRules();
	$permissions = $permissionService->getPermissions();

	return response([
		'rules' => $rules,
		'permissions' => $permissions
	]);
	
});

$app->post($container['administrator'].'/permission', function (Request $request, Response $response, array $args) {
	$permissionService = PermissionService::getInstance();
	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('rule_id', $params)) $params['rule_id'] = false;
	if (!array_key_exists('title', $params)) return responseError(ERROR_0);
	if (!array_key_exists('status', $params)) $params['status'] = 0;
	/* 
		neu ko co action nao = 1 thi ko gui 
		array chuoi (thu tu cua chuoi nhu sau 
		index 0 la permission_id (number)
		index 1 la action xem danh sach(0 hoac 1)
		index 2 la action xem chi tiet(0 hoac 1)
		index 3 la action them(0 hoac 1)
		index 4 la action sua(0 hoac 1)
		index 5 la action xoa(0 hoac 1)
		)
		[
			"1,1,0,0,0,0",
			"2,0,0,0,0,0",
		]
	*/
	if (!array_key_exists('permissions', $params))  $params['permissions'] = [];
	if ($params['rule_id']) {
		$rule_data['id'] = $params['rule_id'];
		$rule_id = $params['rule_id'];
	}
	$rule_data['title'] = $params['title'];
	$rule_data['creator_id'] = $loggedin_user->id;
	$rule_data['status'] = $params['status'];
	$rule_id = $permissionService->saveRule($rule_data);
	if (!$params['rule_id']) {
		$rule_permissions = $permissionService->getPermissionsByRule($params['rule_id']);
		if ($rule_permissions) {
			foreach ($rule_permissions as $key => $rule_permission) {
				$rulePermission = new RulePermission();
				$rulePermission->data->id = $rule_permission->id;
				$rulePermission->where = "id = {$rule_permission->id}";
				$rulePermission->delete(true);
			}
		}
	}

	if ($params['permissions']) {
		foreach ($params['permissions'] as $key => $permissions) {
			$permissions = explode(',', $permissions);
			$permission_data['owner_id'] = $rule_id;
			$permission_data['permission_id'] = $permissions[0];
			$permission_data['get'] = $permissions[2];
			$permission_data['post'] = $permissions[3];
			$permission_data['put'] = $permissions[1];
			$permission_data['patch'] = $permissions[4];
			$permission_data['delete'] = $permissions[5];
			$permission_data['creator_id'] = $loggedin_user->id;
			$permissionService->saveRulePermission($permission_data);
		}
	}
	return response(true);	
});

$app->patch($container['administrator'].'/permission', function (Request $request, Response $response, array $args) {
	$permissionService = PermissionService::getInstance();
	$userService = UserService::getInstance();

	$loggedin_user = loggedin_user();

	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('user_id', $params)) return responseError(ERROR_0);
	if (!array_key_exists('rule_id', $params)) return responseError(ERROR_0);

	$user = $userService->getUserByType($params['user_id'], 'id', false);
	if (!$user) return response(false);

	$rule = $permissionService->getRuleByType($params['rule_id']);
	if (!$rule) return response(false);
	if ($rule->status != 1) return response(false);


	return response($permissionService->setRuleForUser($params['user_id'], $params['rule_id']));
});