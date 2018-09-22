<?php

class GroupService
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

	public function getGroup($conditions, $offset = 0, $limit = 1, $load_more = true, $getAddr = true)
	{
		$table = "amely_groups";
		$groups = $this->db->getData($table, $conditions, $offset, $limit);
		if (!$groups) return false;
		foreach ($groups as $key => $group) {
			$filename = array_pop(explode("/", $group->{"file:avatar"}));
			$file_path = "object/{$group->id}/avatar/images/larger_{$filename}";
			if (file_exists(IMAGE_PATH.$file_path)) {
				$url = IMAGE_URL.$file_path;
			} else {
				$url = AVATAR_DEFAULT;
			}
			$group->avatar = $url;
			$groups[$key] = $group;
		}
		if ($limit == 1) {
			return $groups[0];
		}
		return $groups;
	}

	public function save()
	{
		
	}
	

}