<?php

/**
* 
*/
class BusinessService extends Services
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
        $this->table = "amely_business_pages";
    }

    public function save($data)
    {
    	$business = new Business();
    	$business->data->owner_id = $data['owner_id'];
		$business->data->type = $data['type'];
		$business->data->time_created = $data['time_created'];
		$business->data->title = $data['title'];
		$business->data->description = $data['description'];
		$business->data->subtype = $data['subtype'];
		$business->data->category = $data['category'];
		$business->data->website = $data['website'];
		$business->data->phone = $data['phone'];
		$business->data->address = $data['address'];
		$business->data->inventory_status = $data['inventory_status'];
		$business->data->avatar = $data['avatar'];
		$business->data->cover = $data['cover'];
		$business_id = $business->insert(true);
		return $business_id;
    }

    public function getPageById($id)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => 'id',
			'value' => "= '{$id}'",
			'operation' => ''
		];
		$page = $this->getPage($conditions);
		if (!$page) return false;
		return $page;
    }

    public function getPagesLiked($from)
    {
    	$likeService = LikeService::getInstance();
    	$conditions = null;
	    $conditions[] = [
	    	'key' => 'owner_id',
	    	'value' => "= {$from}",
	    	'operation' => ''
	    ];
	    $conditions[] = [
	    	'key' => 'type',
	    	'value' => "= 'business'",
	    	'operation' => 'AND'
	    ];
	    $likes = $likeService->getLikes($conditions,0,99999999);
	    if (!$likes) return false;
	    return $likes;
    }

    public function getPage($conditions)
	{
		$page = $this->searchObject($conditions, 0, 1);
		if (!$page) return false;
		$page = $this->changeStructureInfo($page);
		return $page;
	}

	public function getPages($conditions, $offset = 0, $limit = 10)
	{
		$pages = $this->searchObject($conditions, $offset, $limit);
		if (!$pages) return false;
		foreach ($pages as $key => $page) {
			$page = $this->changeStructureInfo($page);
			$pages[$key] = $page;
		}
		if (!$pages) return false;
		return array_values($pages);
	}

	private function changeStructureInfo($page)
	{
		$imageService = ImageService::getInstance();

		$page->avatar = $imageService->showAvatar($page->id, $page->avatar, 'business', 'large');
		$page->cover = $imageService->showCover($page->id, $page->cover, 'business', 'large');
		
		return $page;
	}
}