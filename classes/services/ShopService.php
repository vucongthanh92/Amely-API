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
		return $shop;
	}

	public function getShopsLiked($from)
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