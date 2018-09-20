<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});

$app->post($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	$loggedin_user = loggedin_user();
	$params = $request->getParsedBody();
	if (!$params) $params = [];
	if (!array_key_exists("owner_guid", $params)) $params["owner_guid"] = false;
	$owner_guid = $params["owner_guid"];
	if (!is_numeric($owner_guid)) $owner_guid = $loggedin_user->guid;

	if (property_exists($loggedin_user, 'blockedusers')) {
    	$block_list = json_decode($loggedin_user->blockedusers);
    	if (is_array($block_list) && count($block_list) > 0) {
			if (in_array($owner_guid, $block_list)) {
		    	return response([
					"status"  => false,
					"error"   => "User is blocked"
				]);
		    }
		}
	}

	$groups_guid = getGroupsGUID($owner_guid);
	$group_params = null;
	$group_params[] = [
		'key' => 'guid',
		'value' => 'IN ({$groups_guid})',
		'operation' => ''
	];
	$groups = $select->getGroups($group_params,0,9999999);
	if (!$groups) return response(false);
	if ($groups) {
		$groups_guid = $owners = [];
		foreach ($groups as $key => $group) {
			if (!in_array($group->owner_guid, $owners)) {
				array_push($owners, $group->owner_guid);
			}
			if (!in_array($group->guid, $groups_guid)) {
				array_push($groups_guid, $group->guid);
			}
		}
		$groups_guid = implode(',', array_unique($groups_guid));
		$members = getMembersGUID('group', $groups_guid);

		$item_params = null;
		$item_params[] = [
			'key' => 'owner_guid',
			'value' => "IN ({$groups_guid})",
			'operation' => ''
		];
		$item_params[] = [
			'key' => 'quantity',
			'value' => "> 0",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => 'inventory_type',
			'value' => "= 'group'",
			'operation' => 'AND'
		];
		$item_params[] = [
			'key' => '*',
			'value' => 'count',
			'operation' => 'count'
		];
		$item_params[] = [
			'key' => 'owner_guid',
			'value' => "",
			'operation' => 'query_params'
		];
		$item_params[] = [
			'key' => 'owner_guid',
			'value' => "",
			'operation' => 'group_by'
		];

		$items = $select->getItems($item_params,0,999999999);
		foreach ($groups as $key => $group) {
			if ($members) {
				$count = 0;
				foreach ($members as $key => $member) {
					if ($member->relation_from == $group->guid) {
						$count++;
						if (!in_array($member->relation_to, $owners)) {
							array_push($owners, $member->relation_to);
						}
					}
				}
				$group->members_count = $count;
			}
			foreach ($items as $key => $item) {
				if ($item->owner_guid == $group->guid) {
					$group->inventory_items = $item->count;
				}
			}
			$groups[$key] = $group;
		}
		$owners = implode(',', array_unique($owners));
		$user_params = null;
		$user_params[] = [
			'key' => 'guid',
			'value' => "IN ({$owners})",
			'operation' => ''
		];
		$users = $select->getUsers($user_params,0,9999999999,false);
		if ($users) {
			$user_result = [];
			foreach ($users as $key => $user) {
				$user_result[$user->guid] = $user;
			}
		}
	}

	return [
		'groups' => array_values($groups),
		'owners' => $user_result
	];
});

$app->put($container['prefix'].'/groups', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();
	return response(false);
});
