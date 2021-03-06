<?php

/**
* 
*/
class RelationshipService extends Services
{
	protected static $instance = null;

    public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct() 
	{
        $this->table = "amely_relationships r";
    }

    public function save($from, $to, $type, $type_relation = 'invitation', $notify = true)
    {
    	$notificationService = NotificationService::getInstance();
    	$tokenService = TokenService::getInstance();
    	$userService = UserService::getInstance();
    	$relationship = new Relationship;
		$relationship->data->relation_from = $from->id;
		$relationship->data->relation_to = $to->id;
		$relationship->data->type = $type;
		if ($relationship->insert(true)) {
			if ($type == "friend:request") {
				$type = "friend:".$type_relation;
			}
			if ($notify) {
				$notify_params = null;
				$notify_params['from'] = $from;
				$notify_params['to'] = $to;
				return $notificationService->save($notify_params, $type);
			}
			return true;
		}
		return false;
    }

	public function getFriendsGUID($owner_guid)
	{
		$relation_params = null;
		$relation_params[] = [
			'key' => 'amely_relationships r1',
			'value' => "r.relation_from = r1.relation_to",
			'operation' => 'JOIN'
		];
		$relation_params[] = [
			'key' => 'r.relation_to',
			'value' => "= r1.relation_from",
			'operation' => ''
		];
	    $relation_params[] = [
	    	'key' => 'r.type',
	    	'value' => "= 'friend:request'",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'r.relation_from',
	    	'value' => "= {$owner_guid}",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'r.relation_to',
	    	'value' => '',
	    	'operation' => 'query_params'
	    ];
	    $friends = $this->searchObject($relation_params, 0, 999999999);
	    if (!$friends) return false;
	    $friends_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends);
	    return $friends_guid;
	}

	public function getFriendRequested($from, $friends_guid)
	{
		$relation_params = null;
	    $relation_params[] = [
	    	'key' => 'type',
	    	'value' => "= 'friend:request'",
	    	'operation' => ''
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_from',
	    	'value' => "= {$from}",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_to',
	    	'value' => "IN ($friends_guid)",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_to',
	    	'value' => '',
	    	'operation' => 'query_params'
	    ];
	    $friends_requested = $this->searchObject($relation_params, 0, 999999999);
        if ($friends_requested) {
			$friends_requested_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends_requested);
			return $friends_requested_guid;
        }
        return false;
	}

	public function deleteFriend($from, $to)
	{
		$relate = new Relationship;
    	$relate->where = "(relation_from='{$from}' AND relation_to='{$to}' AND type='friend:request') OR
						 (relation_from='{$to}' AND relation_to='{$from}' AND type='friend:request')";
		return $relate->delete();
	}

	public function deleteMemberGroup($from, $to)
	{
		$relate = new Relationship;
    	$relate->where = "(relation_from='{$from}' AND relation_to='{$to}' AND type='group:invite') OR
						 (relation_from='{$to}' AND relation_to='{$from}' AND type='group:approve')";
		return $relate->delete();
	}

	public function getRelationsByType($from = false, $to = false, $type = false, $offset = 0, $limit = 10)
	{
		$relation_params = null;
		if (!$type) return false;
	    $relation_params[] = [
	    	'key' => 'r.type',
	    	'value' => "= '{$type}'",
	    	'operation' => ''
	    ];
		if ($from !== false) {
		    $relation_params[] = [
		    	'key' => 'r.relation_from',
		    	'value' => "= {$from}",
		    	'operation' => 'AND'
		    ];
		}
		if ($to !== false) {
		    $relation_params[] = [
		    	'key' => 'r.relation_to',
		    	'value' => "= {$to}",
		    	'operation' => 'AND'
		    ];
		}
	    $relations = $this->searchObject($relation_params, $offset, $limit);
	    if (!$relations) return false;
	    return $relations;
	}

	public function getRelationByType($from = false, $to = false, $type = false)
	{
		$relation_params = null;
		if (!$type) return false;
	    $relation_params[] = [
	    	'key' => 'r.type',
	    	'value' => "= '{$type}'",
	    	'operation' => ''
	    ];
		if ($from !== false) {
		    $relation_params[] = [
		    	'key' => 'r.relation_from',
		    	'value' => "= {$from}",
		    	'operation' => 'AND'
		    ];
		}
		if ($to !== false) {
		    $relation_params[] = [
		    	'key' => 'r.relation_to',
		    	'value' => "= {$to}",
		    	'operation' => 'AND'
		    ];
		}
	    $relation = $this->searchObject($relation_params, 0, 1);
	    if (!$relation) return false;
	    return $relation;
	}

	public function countRelation($from = false, $to = false, $type = false)
	{
		$relation_params = null;
		if (!$type) return false;
	    $relation_params[] = [
	    	'key' => 'r.type',
	    	'value' => "= '{$type}'",
	    	'operation' => ''
	    ];
	    if ($from !== false) {
		    $relation_params[] = [
		    	'key' => 'r.relation_from',
		    	'value' => "= {$from}",
		    	'operation' => 'AND'
		    ];
		}
		if ($to !== false) {
		    $relation_params[] = [
		    	'key' => 'r.relation_to',
		    	'value' => "= {$to}",
		    	'operation' => 'AND'
		    ];
		}
	    $relation_params[] = [
	    	'key' => '*',
	    	'value' => "count",
	    	'operation' => 'count'
	    ];
	    $relation = $this->searchObject($relation_params, 0, 1);
	    if (!$relation) return false;
	    return $relation->count;
	}

	public function getRelation($conditions)
	{
		$relation = $this->searchObject($conditions, 0, 1);
		if (!$relation) return false;
		return $relation;
	}

	public function getRelations($conditions, $offset = 0, $limit = 10)
	{
		$relations = $this->searchObject($conditions, $offset, $limit);
		if (!$relations) return false;
		return array_values($relations);
	}

}