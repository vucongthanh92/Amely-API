<?php


function select($db, $params) 
{
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
		$query = "SELECT {$parameters} FROM {$params['from']} {$joins} {$wheres} {$order_by} {$group_by} {$limit};";
		$db = $db->prepare($query);
	    $db->execute();
	    $data = $db->fetchAll();
	    return $data;
	}
}

function getData($db, $table, $conditions, $offset = 0, $limit = 2, $load_more = true)
{
	if (!$table) return false;

    $params['from'] = $table;
    $params['load_more']          = $load_more;
    $params['load_more_offset']  = $offset;
    $params['limit']              = $limit;
    $size_image = "default";
    if (is_array($conditions)) {
        foreach ($conditions as $key => $condition) {
           switch ($condition['operation']) {
                case 'count':
                    $params['params'][] = "count(*) as count";
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
                default:
                    $where = $condition['operation']." ".$condition['key']." ".$condition['value'];
                    $params['wheres'][] = $where;
                    break;
            }
        }
    }
    return select($db, $params);
}