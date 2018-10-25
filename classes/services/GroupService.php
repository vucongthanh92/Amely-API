<?php

class GroupService extends Services
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
        $this->table = "amely_groups";
    }

    public function getGroupByType($input, $type ='id')
	{	
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$group = $this->getGroup($conditions);
		if (!$group) return false;
		return $group;
	}

    public function getGroupById($id)
	{
		$conditions = null;
		$conditions[] = [
			'key' => 'id',
			'value' => "= '{$id}'",
			'operation' => ''
		];

		$group = $this->searchObject($conditions, 0, 1);
		if (!$group) return false;
		$group = $this->changeStructureInfo($group);
		return $group;
	}

	public function getGroupsById($id, $offset = 0, $limit = 10)
	{
		$conditions = null;
		$conditions[] = [
			'key' => 'id',
			'value' => "IN ({$id})",
			'operation' => ''
		];

		$groups = $this->searchObject($conditions, $offset, $limit);
		if (!$groups) return false;
		foreach ($groups as $key => $group) {
			$group = $this->changeStructureInfo($group);
			$groups[$key] = $group;
		}
		return $groups;
	}

	public function getGroupsByOwner($input, $offset = 0, $limit = 10)
	{
		$conditions = null;
		$conditions[] = [
			'key' => 'owner_id',
			'value' => "= {$input}",
			'operation' => ''
		];
		$groups = $this->getGroups($conditions, $offset, $limit);
		if (!$groups) return false;
		return $groups;
	}

	public function getGroup($conditions)
	{
		$group = $this->searchObject($conditions, 0, 1);
		if (!$group) return false;
		$group = $this->changeStructureInfo($group);
		return $group;
	}

	public function getGroups($conditions, $offset = 0, $limit = 10)
	{
		$groups = $this->searchObject($conditions, $offset, $limit);
		if (!$groups) return false;
		foreach ($groups as $key => $group) {
			$group = $this->changeStructureInfo($group);
			$groups[$key] = $group;
		}
		
		return array_values($groups);
	}

	public function getIdGroupsApprove($owner_id, $offset, $limit)
	{
		$relationshipService = RelationshipService::getInstance();
		$groups_id = $relationshipService->getRelationsByType(false, $owner_id, 'group:approve', $offset, $limit);
		if (!$groups_id) return false;
		$groups_id = array_unique(array_map(create_function('$o', 'return $o->relation_from;'), $groups_id));
		return $groups_id;
	}

	public function getMembers($group_id, $offset = 0, $limit = 10)
	{
		$relationshipService = RelationshipService::getInstance();
		$members = $relationshipService->getRelationsByType($group_id, false, 'group:approve', $offset, $limit);
		if (!$members) return false;
		return $members;
	}

	public function checkMember($group_id, $member_id)
	{
		$relationshipService = RelationshipService::getInstance();
		$member = $relationshipService->getRelationsByType($group_id, $member_id, 'group:approve', 0, 1);
		if (!$member) return false;
		return $member;
	}

	public function deleteRelationshipGroup($group_id)
	{
		$relate = new Relationship;
    	$relate->where = "(relation_to='{$group_id}' AND type='group:invite') OR
						 (relation_from='{$group_id}' AND type='group:approve')";
		return $relate->delete();
	}

	private function changeStructureInfo($group)
	{
		$imageService = ImageService::getInstance();

		$group->avatar = $imageService->showAvatar($group->id, $group->avatar, 'group', 'larger');
		$group->cover = $imageService->showCover($group->id, $group->cover, 'group', 'larger');

		return $group;
	}
}