<?php

/**
* 
*/
class SlimDatabase
{
	protected static $instance = null;

    public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function updateEAV($object, $show_id = false)
	{
		$arr = [];
		foreach ($object->data as $key => $value) {
			$arr[$key]['value'] = $value;
		}
		$subtypes = array_keys($arr);
		$subtypes = "'".implode("','", $subtypes)."'";
		$query = "SELECT guid, subtype FROM ossn_entities WHERE type = '{$object->type}' AND owner_guid = '{$object->guid}' AND subtype IN ({$subtypes})";
		$db = $this->db->query($query);
		$alldata = [];
	    while($all = $db->fetch_assoc()) {
			$alldata[] = $all;
			$arr[$all['subtype']]["guid"] = $all['guid'];
		}
		foreach ($arr as $ksubtype => $val) {
			$entities_metadata = new stdClass();
			$entities_metadata->guid = $val['guid'];
			$entities_metadata->value = $val['value'];
			$this->saveTableMetaData($entities_metadata, "update");
		}
		return true;
	}

	public function insertEAV($object, $show_id = false)
	{
		$object_guid = $this->saveTableObject($object, true);
		if ($object_guid) {
			foreach ($object->data as $key => $value) {
				$entities = new stdClass();
				$entities->owner_guid = $object_guid;
				$entities->type = $object->type;
				$entities->subtype = $key;
				$entities_guid = $this->saveTableEntities($entities, true);
				$entities_metadata = new stdClass();
				$entities_metadata->guid = $entities_guid;
				$entities_metadata->value = $value;
				$this->saveTableMetaData($entities_metadata);
			}
		}
		if ($show_id) {
			return $object_guid;
		}
		return !!$object_guid;
	}

	public function saveTableObject($object, $action = "insert", $show_id = false) 
	{
		if (!property_exists($object, 'title')) {
			$object->title = null;
		}
		if (!property_exists($object, 'description')) {
			$object->description = null;
		}
		$params['into'] = "ossn_object";

		$params['names']  = array(
			'owner_guid',
			'type',
			'subtype',
			'time_created',
			'title',
			'description'
		);
		$params['values'] = array(
			$object->owner_guid,
			$object->type,
			$object->subtype,
			time(),
			$object->title,
			$object->description
		);
		return $this->switchAction($params, $action, $show_id = false, "guid", property_exists($object, 'guid')?$object->guid:false);
	}

	public function saveTableEntities($entities, $action = "insert", $show_id = false)  
	{
		$params['into'] = "ossn_entities";

		$params['names']  = array(
			'owner_guid',
			'type',
			'subtype',
			'time_created',
			'time_updated',
			'permission',
			'active',
		);
		$params['values'] = array(
			$entities->owner_guid,
			$entities->type,
			$entities->subtype,
			time(),
			0,
			2,
			1
		);
		return $this->switchAction($params, $action, $show_id = false, "guid", property_exists($entities, 'guid')?$entities->guid:false);
	}

	public function saveTableMetaData($entities_metadata, $action = "insert", $show_id = false) 
	{
		$params['into'] = "ossn_entities_metadata";

		$params['names']  = array(
			'guid',
			'value'
		);
		$params['values'] = array(
			$entities_metadata->guid,
			$entities_metadata->value,
		);
		return $this->switchAction($params, $action, $show_id = false, "guid", property_exists($entities_metadata, 'guid')?$entities_metadata->guid:false);
	}

	public function saveTableAnnotations($annotation, $action = "insert", $show_id = false) 
	{
		$params['into'] = "ossn_annotations";

		$params['names']  = array(
			'owner_guid',
			'subject_guid',
			'type',
			'time_created'
		);
		$params['values'] = array(
			$annotation->owner_guid,
			$annotation->subject_guid,
			$annotation->type,
			time()
		);
		return $this->switchAction($params, $action, $show_id = false, "id", property_exists($annotation, 'id')?$annotation->id:false);
	}

	public function saveTableLikes($like, $action = "insert", $show_id = false) 
	{
		$params['into'] = "ossn_likes";

		$params['names']  = array(
			'subject_id',
			'guid',
			'type',
		);
		$params['values'] = array(
			$like->subject_id,
			$like->guid,
			$like->type,
			time()
		);
		return $this->switchAction($params, $action, $show_id = false, "id", property_exists($like, 'id')?$like->id:false);
	}

	public function saveTableNotifications($notification, $action = "insert", $show_id = false) 
	{
		$params['into'] = "ossn_notifications";

		$params['names']  = array(
			'type',
			'poster_guid',
			'owner_guid',
			'subject_guid',
			'viewed',
			'time_created',
			'item_guid'
		);
		$params['values'] = array(
			$notification->type,
			$notification->poster_guid,
			$notification->owner_guid,
			$notification->subject_guid,
			$notification->viewed,
			time(),
			$notification->item_guid
		);
		return $this->switchAction($params, $action, $show_id = false, "guid", property_exists($notification, 'guid')?$notification->guid:false);
	}

	public function saveTableRedeem($redeem, $action = "insert", $show_id = false) 
	{
		$params['into'] = "ossn_redeem_code";

		$params['names']  = array(
			'item_guid',
			'code',
			'expired',
			'quantity',
			'type',
			'guest_guid'
		);
		$params['values'] = array(
			$redeem->item_guid,
			$redeem->code,
			$redeem->expired,
			$redeem->quantity,
			$redeem->type,
			$redeem->guest_guid
		);
		return $this->switchAction($params, $action, $show_id = false, "id", property_exists($redeem, 'id')?$redeem->id:false);
	}


	public function saveTableSite($site, $action = "insert", $show_id = false) 
	{
		$params['into'] = "ossn_site_settings";

		$params['names']  = array(
			'setting_id',
			'name',
			'value'
		);
		$params['values'] = array(
			$site->setting_id,
			$site->name,
			$site->value,
		);
		return $this->switchAction($params, $action, $show_id = false, "setting_id", property_exists($site, 'setting_id')?$site->setting_id:false);
	}

	public function switchAction($params, $action, $show_id = false, $key, $value) 
	{
		switch ($action) {
			case 'insert':
				if ($show_id) {
					return $this->insert($params, true);	
				}
				return $this->insert($params);
				break;
			case 'update':
				$params['wheres'][] = "`{$key}` = {$value}";
				if ($show_id) {
					return $this->update($params, true);	
				}
				return $this->update($params);
				break;
			case 'delete':
				return $this->delete($params);
				break;
			default:
				return false;
				break;
		}
	}

	public function delete($params) {
		if(is_array($params)) {
			$where = implode(' ', $params['wheres']);
			if(!empty($params['wheres'])) {
				$wheres = "WHERE({$where})";
			}
			if(empty($params['wheres'])) {
					return false;
			}
			$query = "DELETE FROM `{$params['from']}` {$wheres};";
			$this->statement($query);
			if($this->execute()) {
					return true;
			}
		}
		return false;
	}

	public function saveTable($object, $table, $action, $show_id = true)
	{
		global $connectDB;
		if (!$table) return false;
		$params = null;
		$params['into']  = $table;
		$query = false;
		$params['names']  = [];
		$params['values'] = [];
		$params['sets'] = [];
		$id_response = 0;
		if (!$object->data) return false;
		foreach ($object->data as $key => $value) {
			array_push($params['names'], $key);
			if ($key == 'sku') {
				array_push($params['values'], $value);
			} else {
				array_push($params['values'], htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
			}
			$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			$params['sets'][] = "`{$key}` = '{$value}'";
			if ($key == 'id') {
				$id_response = $value;
			}
		}
		switch ($action) {
			case 'insert':
				if(count($params['names']) == count($params['values'])) {
					$colums = "`" . implode("`, `", $params['names']) . '`';
					$values = "'" . implode("', '", $params['values']) . "'";
					$query  = "INSERT INTO {$params['into']} ($colums) VALUES ($values)";
				}
				break;
			case 'update':
				if(count($params['sets'])) {
					$q = implode(',', $params['sets']);
					$query = "UPDATE {$params['into']} SET {$q} WHERE {$object->where}";
				}
				break;
			case 'delete':
				if($object->where) {
					$query = "DELETE FROM `{$params['into']}` WHERE {$object->where}";
				}
				break;
			default:
				return false;
				break;
		}

		if ($query === false) return false;
		$db = $connectDB->query($query);
		if ($show_id) {
			switch ($action) {
				case 'update':
					return $id_response;
					break;
				default:
					return $connectDB->insert_id;
					break;
			}
		}
		return true;
	}

	public function insert($params, $show_id = false )
	{
		if(count($params['names']) == count($params['values'])) {
			$colums = "`" . implode("`, `", $params['names']) . '`';
			$values = "'" . implode("', '", $params['values']) . "'";
			$query  = "INSERT INTO {$params['into']} ($colums) VALUES ($values)";
			$this->db->query($query);
			if ($show_id) {
				return $this->db->insert_id;
			}
			return true;
		}
		return false;
	}

	// public function query($query) 
	// {
	// 	$db = $this->db->query($query);
	// 	// var_dump(expression)
	//     // $db->execute();
	//     if (!$db) return false;
	//     if (!property_exists($db, 'num_rows')) return false;
	//     if (!$db->num_rows) return false;
	//     $alldata = [];
	//     while($all = $db->fetch_assoc()) {
	// 		$alldata[] = (object)$all;
	// 	}
	// 	return $alldata;
	// }

	public function select($params) 
	{
		global $connectDB;
		if(is_array($params)) {
			if(!isset($params['params'])) {
					$parameters = '*';
			} else {
					$parameters = implode(', ', $params['params']);
			}
			$order_by = '';
			if(!empty($params['order_by'])) {
					$order_by = "ORDER BY {$params['order_by']}";
			}
			$group_by = '';
			if(!empty($params['group_by'])) {
					$group_by = "GROUP BY {$params['group_by']}";
			}
			$where = '';
			if(isset($params['wheres']) && is_array($params['wheres'])) {
					$where = implode(' ', $params['wheres']);
			}
			$wheres = '';
			if(!empty($params['wheres'])) {
					$wheres = "WHERE({$where})";
			}
			$limit = '';
			if(!empty($params['limit'])) {
					$limit = "LIMIT {$params['limit']}";
			}
			
			$joins = '';
			if(!empty($params['joins']) && !is_array($params['joins'])) {
					$joins = $params['joins'];
			} elseif(!empty($params['joins']) && is_array($params['joins'])) {
					$joins = implode(' ', $params['joins']);
			}
			$offset = "OFFSET {$params['load_more_offset']}";
			$query = "SELECT {$parameters} FROM {$params['from']} {$joins} {$wheres} {$group_by} {$order_by} {$limit} {$offset};";
			$db = $connectDB->query($query);
		    
		    if (!$db) return false;
		    if (!property_exists($db, 'num_rows')) return false;
		    if (!$db->num_rows) return false;
		    $alldata = [];
		    while($all = $db->fetch_assoc()) {
				$alldata[] = (object)$all;
			}
			return $alldata;
		}
	}

	public function getData($table, $conditions, $offset = 0, $limit = 10)
	{
	    $params['from'] = $table;
	    $params['load_more_offset']  = $offset;
	    $params['limit']              = $limit;
	    $size_image = "default";
	    if (is_array($conditions)) {
	        foreach ($conditions as $key => $condition) {
	            switch ($condition['operation']) {
	           		case 'query_params':
	                    $params['params'][] = $condition['key'];
	                    break;
	                case 'count':
	                    $params['params'][] = "count(".$condition['key'].") as ".$condition['value'];
	                    break;
	                case 'order_by':
	                    $params['order_by'] = $condition['key']." ".$condition['value'];
	                    break;
	                case 'group_by':
	                    $params['group_by'] = $condition['key'];
	                    break;
	                case 'image':
	                    $size_image = $condition['value'];
	                    break;
	                case 'JOIN':
	                    $params['joins'] = "JOIN ".$condition['key']." ON ".$condition['value'];
	                    break;
	                case 'LIKE':
	                    $where = $condition['key']." LIKE ".$condition['value'];
	                    $params['wheres'][] = $where;
	                    break;
	                default:
	                    $where = $condition['operation']." ".$condition['key']." ".$condition['value'];
	                    $params['wheres'][] = $where;
	                    break;
	            }
	        }
	    }
	    $result = $this->select($params);
	    if (count($result) > 0) return $result;
	    return false;
	}
}

