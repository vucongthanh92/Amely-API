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

    public function isLiked($from, $to, $type)
    {
    	$conditions = null;
	    $conditions[] = [
	    	'key' => 'id',
	    	'value' => "= {$from}",
	    	'operation' => ''
	    ];
	    $conditions[] = [
	    	'key' => 'type',
	    	'value' => "= '{$type}'",
	    	'operation' => 'AND'
	    ];
	    $conditions[] = [
	    	'key' => 'subject_id',
	    	'value' => "= {$to}",
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
		    	'key' => 'id',
		    	'value' => "= {$from}",
		    	'operation' => ''
		    ];
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'subject_id',
		    	'value' => "= {$to}",
		    	'operation' => 'AND'
		    ];
	    }
	    if (!$from && !$to) return false;
	    $conditions[] = [
	    	'key' => 'type',
	    	'value' => "= '{$type}'",
	    	'operation' => 'AND'
	    ];
	    $conditions[] = [
	    	'key' => '*',
	    	'value' => "count",
	    	'operation' => 'count'
	    ];
	    $like = $this->searchObject($conditions,0,1);
	    if (!$like) return false;
	    return $like->count;
    }

    public function getLikes($conditions, $offset, $limit)
    {
	    $likes = $this->searchObject($conditions, $offset, $limit);
	    if (!$likes) return false;
	    return $likes;
    }

}