<?php

/**
* 
*/
class LikeService extends Services
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
        $this->table = "amely_likes";
    }

    public function save($data)
    {
    	$like = new Like();
    	$like->data->owner_id = $data['owner_id'];
		$like->data->type = $data['type'];
		$like->data->creator_id = $data['creator']->id;
		$like->owner = $data['owner'];

		if ($like->insert()) {
			
			return true;
		}

		return false;
    }

    public function isLiked($from, $to, $type)
    {
    	$conditions = null;
	    $conditions[] = [
	    	'key' => 'owner_id',
	    	'value' => "= {$to}",
	    	'operation' => ''
	    ];
	    $conditions[] = [
	    	'key' => 'type',
	    	'value' => "= '{$type}'",
	    	'operation' => 'AND'
	    ];
	    $conditions[] = [
	    	'key' => 'creator_id',
	    	'value' => "= {$from}",
	    	'operation' => 'AND'
	    ];
	    $like = $this->searchObject($conditions,0,1);
	    if (!$like) return false;
	    return  true;
    }

    public function countLike($from = false, $to = false, $type)
    {
    	$conditions = null;
    	if ($from !== false) {
		    $conditions[] = [
		    	'key' => 'creator_id',
		    	'value' => "= {$from}",
		    	'operation' => ''
		    ];
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'owner_id',
		    	'value' => "= {$to}",
		    	'operation' => 'AND'
		    ];
		     $conditions[] = [
		    	'key' => 'type',
		    	'value' => "= '{$type}'",
		    	'operation' => 'AND'
		    ];
	    }
	    if (!$from && !$to) return false;
	    $conditions[] = [
	    	'key' => '*',
	    	'value' => "count",
	    	'operation' => 'count'
	    ];
	    $like = $this->searchObject($conditions,0,1);
	    if (!$like) return false;
	    return $like->count;
    }

    public function getLike($conditions)
    {
	    $like = $this->searchObject($conditions, 0, 1);
	    if (!$like) return false;
	    return $like;
    }

    public function getLikes($conditions, $offset, $limit)
    {
	    $likes = $this->searchObject($conditions, $offset, $limit);
	    if (!$likes) return false;
	    return $likes;
    }

}