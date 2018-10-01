<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$userService = UserService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists('group_id', $params)) $params['group_id'] = false;
	if (!$params['group_id']) return response(false);
	$group = $groupService->getGroupById($params['group_id']);
	if (!$group) return response(false);
	$group->inventory_items = 0;
	$owners = $userService->getUsersByType($group->owners, 'id', false);
	$group->owners = $owners;

	return response($group);
});

$app->post($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$userService = UserService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $loggedin_user->id;
	if (!array_key_exists('offset', $params)) $params['offset'] = 0;
	if (!array_key_exists('limit', $params)) $params['limit'] = 10;
	$offset = $params['offset'];
	$limit = $params['limit'];
	$owner_id = $params['owner_id'];
	
	$groups_id = $groupService->getIdGroupsApprove($owner_id, $offset, $limit);
	if (!$groups_id) return response(false);
	$groups_id = implode(',', $groups_id);
	$groups = $groupService->getGroupsById($groups_id, 0, 99999999);
	if (!$groups) return response(false);
	$group_owners_id = [];
	foreach ($groups as $key => $group) {
		$group_owners_id = array_merge((array)$group_owners_id, (array)explode(',', $group->owners));
	}
	$group_owners_id = array_unique($group_owners_id);
	if (!$group_owners_id) return response(false);
	$group_owners_id = implode(',', $group_owners_id);
	$users = $userService->getUsersByType($group_owners_id, 'id', false);
	if (!$users) return response(false);

	foreach ($groups as $key => $group) {
		$owner = arrayFilter($users, $group->owner_id);
		$group->owners = $owner;
		$group->inventory_items = 0;
		$groups[$key] = $group;
	}

	return response(array_values($groups));

});

$app->put($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('name', $params)) $params['name'] = false;
	if (!array_key_exists('description', $params)) $params['description'] = false;
	if (!array_key_exists('privacy', $params)) $params['privacy'] = 0;
	if (!array_key_exists('rule', $params)) $params['rule'] = 0;
	if (!array_key_exists('owners', $params)) $params['owners'] = false;

	$group = new Group;
	$group->data->owner_id = $loggedin_user->id;
	$group->data->type = 'user';
	$group->data->title = $params['name'];
	$group->data->description = $params['description'];
	$group->data->privacy = $params['privacy'];
	$group->data->rule = $params['rule'];
	array_push($params['owners'], $loggedin_user->id);
	if ($params['owners']) {
		$params['owners'] = array_unique($params['owners']);
		$owners = implode(',', $params['owners']);
		$group->data->owners = $owners;
	}
	$group_id = $group->insert(true);
	if ($group_id) {
		foreach ($params['owners'] as $key => $owner) {
			$relationship = new Relationship;
			$relationship->data->relation_from = $owner;
			$relationship->data->relation_to = $group_id;
			$relationship->data->type = "group:invite";
			$relationship->insert();

			$relationship = new Relationship;
			$relationship->data->relation_from = $group_id;
			$relationship->data->relation_to = $owner;
			$relationship->data->type = "group:approve";
			$relationship->insert();

		}
		return response($group_id);
	}

	return response(false);
});

$app->patch($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('id', $params)) $params['id'] = false;
	if (!array_key_exists('name', $params)) $params['name'] = false;
	if (!array_key_exists('description', $params)) $params['description'] = false;
	if (!array_key_exists('privacy', $params)) $params['privacy'] = 0;
	if (!array_key_exists('rule', $params)) $params['rule'] = 0;

	if (!$params['id']) return response(false);

	$group = $groupService->getGroupById($params['id']);
	$group = object_cast("Group", $group);
	
	$group->data->title = $params['name'];
	$group->data->description = $params['description'];
	$group->data->privacy = $params['privacy'];
	$group->data->rule = $params['rule'];

	return response($group->update());
});

$app->delete($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists('group_id', $params)) $params['group_id'] = false;
	if (!$params['group_id']) return response(false);
	$group = $groupService->getGroupById($params['group_id']);
	if (!$group) return response(false);
	$group = object_cast("Group", $group);
	$group->where = "id = '{$group->id}'";
	if ($group->type = 'user') {
		if ($loggedin_user->id == $group->owner_id) {
			if ($groupService->deleteRelationshipGroup($group->id)) {
				return response($group->delete());
			}
		}
	}
	return response(false);
});