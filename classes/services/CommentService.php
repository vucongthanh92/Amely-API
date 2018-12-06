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

    public function save($data)
    {
    	$comment = new Annotation();
    	foreach ($data as $key => $value) {
    		$comment->data->$key = $value;
    	}
		return $comment->insert(true);
    }

    public function getCommentById($id)
    {	
    	$conditions = null;
    	$conditions[] = [
    		'key' => 'id',
    		'value' => "= {$id}",
    		'operation' => ''
    	];
    	$comment = $this->searchObject($conditions, 0, 1);
	    if (!$comment) return false;
    	$comment = $this->changeStructureInfo($comment);
	    return $comment;
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
    	$str = "";
    	$conditions = null;
    	if ($from !== false) {
		    $conditions[] = [
		    	'key' => 'owner_id',
		    	'value' => "= {$from}",
		    	'operation' => ''
		    ];
		    $conditions[] = [
		    	'key' => 'type',
		    	'value' => "= '{$type}'",
		    	'operation' => 'AND'
		    ];
		    $str = "AND";
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'creator_id',
		    	'value' => "= {$to}",
		    	'operation' => $str
		    ];
	    }
	    if (!$from && !$to) return false;
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
		    $conditions[] = [
		    	'key' => 'type',
		    	'value' => "= '{$type}'",
		    	'operation' => 'AND'
		    ];
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'creator_id',
		    	'value' => "IN ({$to})",
		    	'operation' => 'AND'
		    ];
	    }
	    if (!$from && !$to) return false;
	    $conditions[] = [
	    	'key' => '*',
	    	'value' => "count",
	    	'operation' => 'count'
	    ];
	    $comment_params[] = [
			'key' => 'creator_id',
			'value' => "",
			'operation' => 'query_params'
		];
		$comment_params[] = [
			'key' => 'creator_id',
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
		if (isset($comment->images)) {
			$images = explode(',', $comment->images);
			if ($images) {
				foreach ($images as $key => $image) {
					$images[$key] = $imageService->showImage($comment->id, $image, 'comment', 'large');
				}
				$comment->images = $images;
			} else {
				unset($comment->images);
			}
		} else {
			unset($comment->images);
		}

		return $comment;
	}

}