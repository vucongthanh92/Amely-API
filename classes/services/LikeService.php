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
	    	'key' => 'guid',
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

}