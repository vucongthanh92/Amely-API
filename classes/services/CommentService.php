<?php

/**
* 
*/
class CommentService extends Services
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
        $this->table = "amely_annotations";
    }

    public function getComments($conditions, $offset, $limit)
    {
    	$comments = $this->searchObject($conditions, $offset, $limit);
	    if (!$comments) return false;
	    return $comments;
    }

    public function countComment($from = false, $to = false, $type)
    {
    	$conditions = null;
    	if ($from !== false) {
		    $conditions[] = [
		    	'key' => 'owner_guid',
		    	'value' => "= {$from}",
		    	'operation' => ''
		    ];
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'subject_guid',
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
	    $comment = $this->searchObject($conditions,0,1);
	    return  $comment->count;
    }

    public function countComments($from = false, $to = false, $type)
    {
    	$conditions = null;
    	if ($from !== false) {
		    $conditions[] = [
		    	'key' => 'owner_guid',
		    	'value' => "IN ({$from})",
		    	'operation' => ''
		    ];
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'subject_guid',
		    	'value' => "IN ({$to})",
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
	    $comment_params[] = [
			'key' => 'subject_guid',
			'value' => "",
			'operation' => 'query_params'
		];
		$comment_params[] = [
			'key' => 'subject_guid',
			'value' => "",
			'operation' => 'group_by'
		];
	    $comments = $this->searchObject($conditions,0,99999999);
	    if (!$comments) return false;
	    return  $comments;
    }

}