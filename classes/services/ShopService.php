<?php

/**
* 
*/
class ShopService extends Services
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
        $this->table = "amely_shops";
    }

    public function save($data)
    {
// $snapshot = new Snapshot();
// $snapshot->data->owner_id = $data['owner_id'];
// $snapshot->data->type = $data['type'];
// $snapshot->data->time_created = $data['time_created'];
// $snapshot->data->title = $data['title'];
// $snapshot->data->description = $data['description'];
// $snapshot->data->sku = $data['sku'];
// $snapshot->data->model = $data['model'];
// $snapshot->data->tax = $data['tax'];
// $snapshot->data->weight = $data['weight'];
// $snapshot->data->expiry_type = $data['expiry_type'];
// $snapshot->data->currency = $data['currency'];
// $snapshot->data->origin = $data['origin'];
// $snapshot->data->storage_duration = $data['storage_duration'];
// $snapshot->data->is_special = $data['is_special'];
// $snapshot->data->product_group = $data['product_group'];
// $snapshot->data->creator_id = $data['creator_id'];
// $snapshot->data->custom_attributes = $data['custom_attributes'];
// $snapshot->data->duration = $data['duration'];
// $snapshot->data->begin_day = $data['begin_day'];
// $snapshot->data->end_day = $data['end_day'];
// $snapshot->data->manufacturer = $data['manufacturer'];
// $snapshot->data->price = $data['price'];
// $snapshot->data->sale_price = $data['sale_price'];
// $snapshot->data->unit = $data['unit'];
// $snapshot->data->adjourn_price = $data['adjourn_price'];
// $snapshot->data->code = $data['code'];
// $snapshot->data->images = $data['images'];
// $snapshot->data->parent_id = $data['parent_id'];
// $snapshot_id = $snapshot->insert(true);
// return $snapshot_id;
    }

    public function getShopByType($input, $type ='id', $getAddr = true)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$shop = $this->getShop($conditions, $getAddr);
		if (!$shop) return false;
		return $shop;
    }

    public function getShopsByType($input, $type ='id', $offset = 0, $limit = 10, $getAddr = true)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$shops = $this->getShops($conditions, $offset, $limit, $getAddr);
		if (!$shops) return false;
		return $shops;
    }

    public function getShop($conditions, $getAddr = true)
	{
		$shop = $this->searchObject($conditions, 0, 1);
		if (!$shop) return false;
		$shop = $this->changeStructureInfo($shop, $getAddr);
		return $shop;
	}

	public function getShops($conditions, $offset = 0, $limit = 10, $getAddr = true)
	{
		$shops = $this->searchObject($conditions, $offset, $limit);
		if (!$shops) return false;
		foreach ($shops as $key => $shop) {
			$shop = $this->changeStructureInfo($shop, $getAddr);
			$shops[$key] = $shop;
		}
		return $shops;
	}

	public function getShopsLiked($from, $to = false)
    {
    	$likeService = LikeService::getInstance();
    	$conditions = null;
	    $conditions[] = [
	    	'key' => 'owner_id',
	    	'value' => "= {$from}",
	    	'operation' => ''
	    ];
	    if ($to) {
	    	$conditions[] = [
		    	'key' => 'subject_id',
		    	'value' => "= {$to}",
		    	'operation' => 'AND'
		    ];	
	    }
	    $conditions[] = [
	    	'key' => 'type',
	    	'value' => "= 'shop'",
	    	'operation' => 'AND'
	    ];
	    $likes = $likeService->getLikes($conditions,0,99999999);
	    if (!$likes) return false;
	    return $likes;
    }

	private function changeStructureInfo($shop, $getAddr = true)
	{
		$addressService = AddressService::getInstance();
		$imageService = ImageService::getInstance();

		$shop->avatar = $imageService->showAvatar($shop->id, $shop->avatar, 'shop', 'larger');
		$shop->cover = $imageService->showCover($shop->id, $shop->cover, 'shop', 'larger');

	    $shop->description = html_entity_decode($shop->description);
	    $shop->introduce = html_entity_decode($shop->introduce);
	    $shop->policy = html_entity_decode($shop->policy);
	    $shop->contact = html_entity_decode($shop->contact);

	    if ($getAddr) {
	    	if ($shop->owner_province && $shop->owner_district && $shop->owner_ward) {
			    $owner_province = $addressService->getAddress($shop->owner_province, 'province');
			    $owner_district = $addressService->getAddress($shop->owner_district, 'district');
			    $owner_ward = $addressService->getAddress($shop->owner_ward, 'ward');
			    $owner_province = $owner_province->type .' '. $owner_province->name;
			    $owner_district = $owner_district->type .' '. $owner_district->name;
			    $owner_ward = $owner_ward->type .' '. $owner_ward->name;
			    $shop->owner_full_address = $shop->owner_address.', '.$owner_ward.', '.$owner_district.', '.$owner_province;
	    	}
	    }

		return $shop;
	}
}