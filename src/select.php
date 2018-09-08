<?php
$container = $app->getContainer();

function getAddress($db, $id, $type)
{
	$table = "ossn_".$type.'s';
	$conditions[] = null;
	$key = $type."id";
	$conditions[] = [
		'key' => $key,
		'value' => "= '{$id}'",
		'operation' => ''
	];
	$addr = getData($db, $table, $conditions, 0, 1, $load_more);
	return $addr[0];
}

function getUsers($db, $conditions, $offset = 0, $limit = 10, $load_more = true)
{
	$table = "users";
	$users = getData($db, $table, $conditions, $offset, $limit, $load_more);
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
		$user_province = getAddress($db, $user->province, 'province');
		$user_district = getAddress($db, $user->district, 'district');
		$user_ward = getAddress($db, $user->ward, 'ward');

	    $user_province = $user_province->type .' '. $user_province->name;
	    $user_district = $user_district->type .' '. $user_district->name;
	    $user_ward = $user_ward->type .' '. $user_ward->name;
	    $user->full_address = $user->address.', '.$user_ward.', '.$user_district.', '.$user_province;

		$users[$key] = $user;
	}
	if ($limit == 1) {
		return $users[0];
	}
	return $users;
}

function getShops($db, $conditions, $offset = 0, $limit = 10, $load_more = true)
{
	$table = "shops";
	$shops = getData($db, $table, $conditions, $offset, $limit, $load_more);
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


	    $owner_province = getAddress($db, $shop->owner_province, 'province');
	    $owner_district = getAddress($db, $shop->owner_district, 'district');
	    $owner_ward = getAddress($db, $shop->owner_ward, 'ward');
	    $owner_province = $owner_province->type .' '. $owner_province->name;
	    $owner_district = $owner_district->type .' '. $owner_district->name;
	    $owner_ward = $owner_ward->type .' '. $owner_ward->name;
	    $shop->owner_full_address = $shop->owner_address.', '.$owner_ward.', '.$owner_district.', '.$owner_province;

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