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


	$owners = $userService->getUsersByType($group->owners_id, 'id', false);
	$group->owners = $owners;

	$members = $groupService->getMembers($group->id, 0, 99999999);
	if ($members) {
		$members = array_map(create_function('$o', 'return $o->relation_to;'), $members);
		$members = implode(',', $members);
		$members = $userService->getUsersByType($members, 'id', false);
		$group->members = $members;
	}

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
		$group_owners_id = array_merge((array)$group_owners_id, (array)explode(',', $group->owners_id));
	}
	$group_owners_id = array_unique($group_owners_id);
	if (!$group_owners_id) return response(false);
	$group_owners_id = implode(',', $group_owners_id);
	$users = $userService->getUsersByType($group_owners_id, 'id', false);
	if (!$users) return response(false);

	foreach ($groups as $key => $group) {
		$owner = arrayFilter($users, $group->owner_id);
		$group->members_count = $groupService->countMembers($group->id);
		$group->owners = $owner;
		$group->inventory_items = 0;
		$groups[$key] = $group;
	}

	return response(array_values($groups));

});

$app->put($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$relationshipService = RelationshipService::getInstance();
	$groupService = GroupService::getInstance();
	$userService = UserService::getInstance();
	$services = Services::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists('name', $params)) $params['name'] = false;
	if (!array_key_exists('description', $params)) $params['description'] = false;
	if (!array_key_exists('privacy', $params)) $params['privacy'] = 0;
	if (!array_key_exists('rule', $params)) $params['rule'] = 0;
	if (!array_key_exists('owners', $params)) $params['owners'] = false;
	if (!array_key_exists('member_invite', $params)) $params['member_invite'] = false;

	$group_params['owner_id'] = $loggedin_user->id;
	$group_params['type'] = 'user';
	$group_params['title'] = $params['name'];
	$group_params['description'] = $params['description'];
	$group_params['privacy'] = $params['privacy'];
	$group_params['rule'] = $params['rule'];

	array_push($params['owners'], $loggedin_user->id);
	if ($params['owners']) {
		$params['owners_id'] = array_unique($params['owners']);
		$group_params['owners_id'] = implode(',', $params['owners_id']);
	}

	$group_id = $groupService->save($group_params);
	if ($group_id) {
		$inventoryService = InventoryService::getInstance();
		$inventoryService->save($group_id, 'group', $loggedin_user->id);
		$group = $groupService->getGroupByType($group_id, 'id');
		if ($params['owners_id']) {
			foreach ($params['owners_id'] as $key => $owner_id) {
				$user = $userService->getUserByType($owner_id, 'id', false);

				$relationshipService->save($user, $group, 'group:invite');
				$relationshipService->save($group, $user, 'group:approve');
				if ($owner_id != $loggedin_user->id) {
					$relationshipService->save($group, $user, 'group:joined');
				}

				$services->memberGroupFB($group_id, $user->username, 'add');
			}
		}
		if ($params['member_invite']) {
			foreach ($params['member_invite'] as $key => $member_id) {
				$user = $userService->getUserByType($member_id, 'id', false);

				$relationshipService->save($user, $group, 'group:invite');
				$relationshipService->save($group, $user, 'group:approve');
				if ($member_id != $loggedin_user->id) {
					$relationshipService->save($group, $user, 'group:joined');
				}

				$services->memberGroupFB($group_id, $user->username, 'add');
			}
		}
		$services->createGroupFB($loggedin_user->username, $group_id, $params['name']);
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
	if (!array_key_exists('owner_id', $params)) $params['owner_id'] = $loggedin_user->id;

	if (!$params['id']) return response(false);

	$group = $groupService->getGroupById($params['id']);
	$group = object_cast("Group", $group);
	if ($group->owner_id != $loggedin_user->id) return response(false);
	if ($group->owners_id) {
		$owners_id = explode(',', $group->owners_id);
		$owners_id = array_diff($owners_id, [$group->owner_id]);
		array_push($owners_id, $params['owner_id']);
		$group->data->owners_id = implode(',', $owners_id);
	}
	$group->data->owner_id = $params['owner_id'];
	$group->data->title = $params['name'];
	$group->data->description = $params['description'];
	$group->data->privacy = $params['privacy'];
	$group->data->rule = $params['rule'];
	$group->data->id = $group->id;
	$group->where = "id = {$group->id}";
	return response($group->update(true));
});

$app->delete($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$services = Services::getInstance();
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
				$services->deleteGroupFB($group->id);
				// $services->memberGroupFB($group->id, $user->username, 'delete')
				return response($group->delete());
			}
		}
	}
	return response(false);
});