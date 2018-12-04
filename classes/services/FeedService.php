<?php

class FeedService extends Services
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
        $this->table = "amely_feeds";
    }

    public function getFeedById($id)
    {
        $conditions = null;
        $conditions[] = [
            'key' => 'id',
            'value' => "= {$id}",
            'operation' => ''
        ];
        $feed = $this->getFeed($conditions);
        if (!$feed) return false;
        return $feed;
    }

    public function getFeedsByType($input, $type = 'user')
    {
        $conditions = null;
        $conditions[] = [
            'key' => $type,
            'value' => "IN {$input}",
            'operation' => ''
        ];
        $feeds = $this->getFeeds($conditions, 0, 9999999999);
        if (!$feeds) return false;
        return $feeds;
    }

    public function getFeedByType($input, $type = 'user')
    {
        $conditions = null;
        $conditions[] = [
            'key' => $type,
            'value' => "= {$input}",
            'operation' => ''
        ];
        $feed = $this->getFeed($conditions);
        if (!$feed) return false;
        return $feed;
    }

    public function getFeed($conditions, $info = true)
    {
        $feed = $this->searchObject($conditions, 0, 1);
        if (!$feed) return false;
        if ($info) {
            $feed = $this->changeStructureInfo($feed);
        }
        return $feed;
    }

    public function getFeeds($conditions, $offset = 0, $limit = 10)
    {
        $feeds = $this->searchObject($conditions, $offset, $limit);
        if (!$feeds) return false;
        foreach ($feeds as $key => $feed) {
            $feeds[$key] = $this->changeStructureInfo($feed);
        }
        return $feeds;
    }

    public function countLike($feed_id)
    {
        $likeService = LikeService::getInstance();
        $count = $likeService->countLike(false, $feed_id, 'feed');
        if (!$count) return 0;
        return $count;
    }

    public function countComment($feed_id)
    {
        $commentService = CommentService::getInstance();
        $count = $commentService->countComment($feed_id, false, 'feed');
        if (!$count) return 0;
        return $count;
    }

    public function getFeedsCountComment($feeds_id)
    {
        $commentService = CommentService::getInstance();
        $comment_params = null;
        $comment_params[] = [
            'key' => 'type',
            'value' => "= 'feed'",
            'operation' => ''
        ];
        $comment_params[] = [
            'key' => 'owner_id',
            'value' => "IN ({$feeds_id})",
            'operation' => 'AND'
        ];
        $comment_params[] = [
            'key' => '*',
            'value' => "count",
            'operation' => 'count'
        ];
        $comment_params[] = [
            'key' => 'owner_id',
            'value' => "",
            'operation' => 'query_params'
        ];
        $comment_params[] = [
            'key' => 'owner_id',
            'value' => "",
            'operation' => 'group_by'
        ];
        $comments = $commentService->getComments($comment_params, 0, 99999999);
        if (!$comments) return false;
        return $comments;
    }

    public function getFeedsCountLike($feeds_id)
    {
        $likeService = LikeService::getInstance();

        $like_params = null;
        $like_params[] = [
            'key' => 'owner_id',
            'value' => "IN ({$feeds_id})",
            'operation' => ''
        ];
        $like_params[] = [
            'key' => 'type',
            'value' => "= 'feed'",
            'operation' => 'AND'
        ];
        $like_params[] = [
            'key' => '*',
            'value' => "count",
            'operation' => 'count'
        ];
        $like_params[] = [
            'key' => 'owner_id',
            'value' => "",
            'operation' => 'query_params'
        ];
        $like_params[] = [
            'key' => 'owner_id',
            'value' => "",
            'operation' => 'group_by'
        ];
        $likes = $likeService->getLikes($like_params, 0, 99999999);
        if (!$likes) return false;
        return $likes; 
    }
    public function getFeedsLiked($owner_id, $feeds_id)
    {
        $likeService = LikeService::getInstance();

        $like_params = null;
        $like_params[] = [
            'key' => 'owner_id',
            'value' => "IN ({$feeds_id})",
            'operation' => ''
        ];
        $like_params[] = [
            'key' => 'type',
            'value' => "= 'feed'",
            'operation' => 'AND'
        ];
        $like_params[] = [
            'key' => 'owner_id',
            'value' => "",
            'operation' => 'query_params'
        ];
        $like_params[] = [
            'key' => 'owner_id',
            'value' => "",
            'operation' => 'group_by'
        ];
        $likes = $likeService->getLikes($like_params,0,99999999);
        if (!$likes) return false;
        return $likes; 
    }

    private function changeStructureInfo($feed)
    {   
        foreach ($variable as $key => $value) {
            # code...
        }
        $imageService = ImageService::getInstance();
        if ($feed->images) {
            $images = explode(',', $feed->images);
            $feed->images = null;
            $feed->images = [];
            foreach ($images as $image) {
                array_push($feed->images, $imageService->showImage($feed->id, $image, 'feed', 'large'));
            }
        }
        
        return $feed;
    }
}