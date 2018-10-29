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
		$comment->data->owner_id = $data['owner_id'];
		$comment->data->type = $data['type'];
		$comment->data->creator_id = $data['creator']->id;
		if ($data['content']) {
			$comment->data->content = $data['content'];
		}
		$comment_id = $comment->insert(true);
		if ($comment_id) {
			$notificationService = NotificationService::getInstance();
			$notify_params = null;
			$notify_params['owner_id'] = $data['owner']->id;
			$notify_params['type'] = 'user';
			$notify_params['from_id'] = $data['creator_id'];
			$notify_params['from_type'] = 'user';
			$notify_params['subject_id'] = $data['subject_id'];
			$notify_params['subject_type'] = 'comments:post';
			$notify_params['item_id'] = null;
			$notify_params['notify_token'] = $data['owner']->notify_token;
			$notify_params['title'] = $data['creator']->fullname." ".COMMENT;
			$notify_params['description'] = "";
			
			return response($notificationService->save($notify_params));
		}
		return false;
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
    	}
	    if ($to !== false) {
		    $conditions[] = [
		    	'key' => 'creator_id',
		    	'value' => "= {$to}",
		    	'operation' => 'AND'
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