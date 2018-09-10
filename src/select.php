<?php
ini_get('open_basedir');

/**
* 
*/
class SlimSelect extends SlimDatabase
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function getAddress($id, $type)
	{
		$table = "ossn_".$type.'s';
		$conditions[] = null;
		$key = $type."id";
		$conditions[] = [
			'key' => $key,
			'value' => "= '{$id}'",
			'operation' => ''
		];
		$addr = $this->getData($table, $conditions, 0, 1, false);
		return $addr[0];
	}

	public function getUsers($conditions, $offset = 0, $limit = 10, $load_more = true, $getAddr = true)
	{
		$table = "users";
		$users = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$users) return false;
		foreach ($users as $key => $user) {
			unset($user->password);
			unset($user->salt);
			$user->fullname = $user->last_name.' '.$user->first_name;
			$avatar = str_replace('profile/photo/', '', $user->avatar);
			$cover = str_replace('profile/cover/', '', $user->cover);

			$avatar_path = "/user/{$user->guid}/profile/photo/"."larger_{$avatar}";
			$cover_path = "/user/{$user->guid}/profile/cover/"."larger_{$cover}";
			if (file_exists(IMAGE_PATH.$avatar_path)) {
				$user->avatar = IMAGE_URL.$avatar_path;
			} else {
				$user->avatar = AVATAR_DEFAULT;
			}
			if (file_exists(IMAGE_PATH.$cover_path)) {
				$user->cover = IMAGE_URL.$cover_path;	
			} else {
				$user->cover = COVER_DEFAULT;
			}
			if ($getAddr) {
				$user_province = $this->getAddress($user->province, 'province');
				$user_district = $this->getAddress($user->district, 'district');
				$user_ward = $this->getAddress($user->ward, 'ward');

			    $user_province = $user_province->type .' '. $user_province->name;
			    $user_district = $user_district->type .' '. $user_district->name;
			    $user_ward = $user_ward->type .' '. $user_ward->name;
			    $user->full_address = $user->address.', '.$user_ward.', '.$user_district.', '.$user_province;
			}

			$users[$key] = $user;
		}
		if ($limit == 1) {
			return $users[0];
		}
		return $users;
	}

	public function getShops($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "shops";
		$shops = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$shops) return false;
		foreach ($shops as $key => $shop) {
			$avatar = array_pop(explode("/", $shop->avatar));
			$cover = array_pop(explode("/", $shop->cover));
			$avatar_path = "/object/{$shop->guid}/avatar/images/"."larger_{$avatar}";
			$cover_path = "/object/{$shop->guid}/cover/images/"."larger_{$avatar}";
			if (file_exists(IMAGE_PATH.$avatar_path)) {
				$shop->avatar = IMAGE_URL.$avatar_path;
			} else {
				$shop->avatar = AVATAR_DEFAULT;
			}
			if (file_exists(IMAGE_PATH.$cover_path)) {
				$shop->cover = IMAGE_URL.$cover_path;	
			} else {
				$shop->cover = COVER_DEFAULT;
			}

		    $shop->description = html_entity_decode($shop->description);
		    $shop->introduce = html_entity_decode($shop->introduce);
		    $shop->policy = html_entity_decode($shop->policy);
		    $shop->contact = html_entity_decode($shop->contact);


		    $owner_province = $this->getAddress($shop->owner_province, 'province');
		    $owner_district = $this->getAddress($shop->owner_district, 'district');
		    $owner_ward = $this->getAddress($shop->owner_ward, 'ward');
		    $owner_province = $owner_province->type .' '. $owner_province->name;
		    $owner_district = $owner_district->type .' '. $owner_district->name;
		    $owner_ward = $owner_ward->type .' '. $owner_ward->name;
		    $shop->owner_full_address = $shop->owner_address.', '.$owner_ward.', '.$owner_district.', '.$owner_province;

		    $shop_province = $this->getAddress($shop->shop_province, 'province');
		    $shop_district = $this->getAddress($shop->shop_district, 'district');
		    $shop_ward = $this->getAddress($shop->shop_ward, 'ward');
		    $shop_province = $shop_province->type .' '. $shop_province->name;
		    $shop_district = $shop_district->type .' '. $shop_district->name;
		    $shop_ward = $shop_ward->type .' '. $shop_ward->name;
		    $shop->full_address = $shop->shop_address.', '.$shop_ward.', '.$shop_district.', '.$shop_province;

		   //  if ($shop->files_scan) {
		   //  	$files_scan = [];
		   //  	$files = explode(";", $shop->files_scan);
		   //  	foreach ($files as $kfile_scan => $vfile_scan) {
		   //  		$photo = str_replace('shop/images/', '', $vfile_scan);
					// $image_file_scan = market_photo_url($shop->guid, $photo, 'shop', 'large');
					// array_push($files_scan, $image_file_scan);
		   //  	}
		   //  	$shop->files_scan = implode(";", $files_scan);
		   //  }

		    $shops[$key] = $shop;
		}
		if ($limit == 1) {
			return $shops[0];
		}
		return $shops;
	}

	public function getStores($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "stores";
		$stores = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$stores) return false;
		foreach ($stores as $key => $store) {

			$store_province = $this->getAddress($shop->owner_province, 'province');
			$store_district = $this->getAddress($shop->shop_district, 'district');
			$store_ward = $this->getAddress($shop->shop_ward, 'ward');

		    $store_province = $store_province->type .' '. $store_province->name;
		    $store_district = $store_district->type .' '. $store_district->name;
		    $store_ward = $store_ward->type .' '. $store_ward->name;
		    $store->full_address = $store->address.' '.$store_ward.' '.$store_district.' '.$store_province;

		    $stores[$key] = $store;
		}
		if ($limit == 1) {
			return $stores[0];
		}
		return $stores;
	}

	public function getRelationships($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_relationships";
		$ossn_relationships = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $ossn_relationships[0];
		}
		return $ossn_relationships;
	}

	public function getLikes($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_likes";
		$ossn_likes = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $ossn_likes[0];
		}
		return $ossn_likes;
	}

	public function getSiteSettings($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_site_settings";
		$settings = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $settings[0];
		}
		return $settings;
	}

	public function getProductGroup($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "product_group";
		$product_groups = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $product_groups[0];
		}
		return $product_groups;
	}
	
}
