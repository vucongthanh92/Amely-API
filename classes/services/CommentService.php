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
	    foreach ($comments as $key => $comment) {
	    	$comment = $this->changeStructureInfo($comment);
	    	$comments[$key] = $comment;
	    }
	    return $comments;
    }

    public function countComment($from = false, $to = false, $type)
    {
    	$conditions = null;
    	if ($from !== false) {
		    $conditions[] = [
		    	'key' => 'owner_id',
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
	    $comment = $this->searchObject($conditions,0,1);
	    if (!$comment) return false;
	    return  $comment->count;
    }

    public function countComments($from = false, $to = false, $type)
    {
    	$conditions = null;
    	if ($from !== false) {
		    $conditions[] = [
		    	'key' => 'owner_id',
		    	'value' => "IN ({$from})",
		    	'operation' => ''
		    ];
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'subject_id',
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
			'key' => 'subject_id',
			'value' => "",
			'operation' => 'query_params'
		];
		$comment_params[] = [
			'key' => 'subject_id',
			'value' => "",
			'operation' => 'group_by'
		];
	    $comments = $this->searchObject($conditions,0,99999999);
	    if (!$comments) return false;
	    return  $comments;
    }

    private function changeStructureInfo($comment)
	{
		$imageService = ImageService::getInstance();
		if ($comment->images) {
			$images = explode(',', $comment->images);
			foreach ($images as $key => $image) {
				$images[$key] = $imageService->showImage($comment->id, $image, 'comment', 'large');
			}
			$comment->images = $images;
		}

		return $comment;
	}

}