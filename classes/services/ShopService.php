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

    public function getShop($conditions, $getAddr = true)
	{
		$addressService = AddressService::getInstance();
		$shop = $this->searchObject($conditions, 0, 1);
		if (!$shop) return false;
		$shop = $this->changeStructureInfo($shop, $getAddr);
		return $shop;
	}

	public function getShops($conditions, $offset = 0, $limit = 10, $getAddr = true)
	{
		$addressService = AddressService::getInstance();
		$shops = $this->searchObject($conditions, $offset, $limit);
		if (!$shops) return false;
		foreach ($shops as $key => $shop) {
			$avatar_path = "/shop/{$shop->id}/avatar/"."larger_{$shop->avatar}";
			$cover_path = "/shop/{$shop->id}/cover/"."larger_{$shop->cover}";

			$shop->avatar = checkPath($avatar_path);
			$shop->cover = checkPath($avatar_path,'cover');
			
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

		    	if ($shop->shop_province && $shop->shop_district && $shop->shop_ward) {
				    $shop_province = $addressService->getAddress($shop->shop_province, 'province');
				    $shop_district = $addressService->getAddress($shop->shop_district, 'district');
				    $shop_ward = $addressService->getAddress($shop->shop_ward, 'ward');
				    $shop_province = $shop_province->type .' '. $shop_province->name;
				    $shop_district = $shop_district->type .' '. $shop_district->name;
				    $shop_ward = $shop_ward->type .' '. $shop_ward->name;
				    $shop->full_address = $shop->shop_address.', '.$shop_ward.', '.$shop_district.', '.$shop_province;
		    	}
		    }
			$shops[$key] = $shop;
		}
		if (!$shops) return false;
		return array_values($shops);
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

	    	if ($shop->shop_province && $shop->shop_district && $shop->shop_ward) {
			    $shop_province = $addressService->getAddress($shop->shop_province, 'province');
			    $shop_district = $addressService->getAddress($shop->shop_district, 'district');
			    $shop_ward = $addressService->getAddress($shop->shop_ward, 'ward');
			    $shop_province = $shop_province->type .' '. $shop_province->name;
			    $shop_district = $shop_district->type .' '. $shop_district->name;
			    $shop_ward = $shop_ward->type .' '. $shop_ward->name;
			    $shop->full_address = $shop->shop_address.', '.$shop_ward.', '.$shop_district.', '.$shop_province;
	    	}
	    }

		return $shop;
	}
}