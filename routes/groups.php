<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists("id", $params)) $params["id"] = false;
	if (!$params['id']) return response(false);
	$group = $groupService->getGroupById($params['id']);
	if (!$group) return response(false);

	return response($group);
});

$app->post($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$userService = UserService::getInstance();

	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("owner_guid", $params)) $params["owner_guid"] = false;
	if (!array_key_exists("offset", $params)) $params["offset"] = 0;
	if (!array_key_exists("limit", $params)) $params["limit"] = 10;

	$owner_guid = $loggedin_user->id;
	if ($params["owner_guid"]) $owner_guid = $params["owner_guid"];

	$groups = $groupService->getGroupsByOwner($owner_guid, $params["offset"], $params["limit"]);
	if (!$groups) return response(false);
	$group_owners = [];
	foreach ($groups as $key => $group) {
		if ($group->owners) {
			$owners = explode(',', $group->owners);
			$group_owners = array_merge((array)$group_owners, (array)$owners);
		}
	}
	$group_owners = array_unique($group_owners);
	if (!$group_owners) return response(false);
	$group_owners = implode(',', $group_owners);
	$group_users = [];
	$users = $userService->getUsersByType($group_owners, 'id', false);
	foreach ($users as $key => $user) {
		$group_users[$user->id] = $user;
	}		

	return response([
		'groups' => array_values($groups),
		'owners' => $group_users
	]);
});

$app->put($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("name", $params)) $params["name"] = false;
	if (!array_key_exists("description", $params)) $params["description"] = false;
	if (!array_key_exists("privacy", $params)) $params["privacy"] = 0;
	if (!array_key_exists("rule", $params)) $params["rule"] = 0;
	if (!array_key_exists("owners", $params)) $params["owners"] = false;

	$group = new Group;
	$group->data->owner_guid = $loggedin_user->id;
	$group->data->type = 'user';
	$group->data->title = $params["name"];
	$group->data->description = $params["description"];
	$group->data->privacy = $params["privacy"];
	$group->data->rule = $params["rule"];
	if ($params['owners']) {
		array_push($params['owners'], $loggedin_user->id);
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
		return response(['id' => $group_id]);
	}

	return response(false);
});

$app->patch($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("id", $params)) $params["id"] = false;
	if (!array_key_exists("name", $params)) $params["name"] = false;
	if (!array_key_exists("description", $params)) $params["description"] = false;
	if (!array_key_exists("privacy", $params)) $params["privacy"] = 0;
	if (!array_key_exists("rule", $params)) $params["rule"] = 0;

	if (!$params['id']) return response(false);

	$group = $groupService->getGroupById($params['id']);
	$group = object_cast("Group", $group);
	
	$group->data->title = $params["name"];
	$group->data->description = $params["description"];
	$group->data->privacy = $params["privacy"];
	$group->data->rule = $params["rule"];

	return response($group->update());
});

$app->delete($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$groupService = GroupService::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getQueryParams();
	if (!array_key_exists("id", $params)) $params["id"] = false;
	if (!$params['id']) return response(false);
	$group = $groupService->getGroupById($params['id']);
	if (!$group) return response(false);
	$group = object_cast("Group", $group);
	$group->where = "id = '{$group->id}'";
	if ($group->type = 'user') {
		if ($loggedin_user->id == $group->owner_guid) {
			if ($groupService->deleteRelationshipGroup($group->id)) {
				return response($group->delete());
			}
		}
	}
	return response(false);
});