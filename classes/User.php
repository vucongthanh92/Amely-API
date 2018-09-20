<?php

/**
* 
*/
class User
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct() {
        $this->db = SlimDatabase::getInstance();
        $this->select = SlimSelect::getInstance();
        $this->services = Services::getInstance();
        $this->table = "amely_users";
    }

	public function getUser($conditions, $getAddr = true, $password = true)
	{
		$users = $this->db->getData($this->table, $conditions,0,1);
		if (!$users) return false;
		$user = $users[0];
		if ($password) {
			unset($user->password);
			unset($user->salt);
			unset($user->verification_code);
			unset($user->activation);
		}
		$user->fullname = $user->last_name.' '.$user->first_name;
		$avatar = str_replace('profile/photo/', '', $user->avatar);
		$cover = str_replace('profile/cover/', '', $user->cover);

		$avatar_path = "/user/{$user->id}/profile/photo/"."larger_{$avatar}";
		$cover_path = "/user/{$user->id}/profile/cover/"."larger_{$cover}";
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
			if ($user->province && $user->district && $user->ward) {
				$user_province = $this->select->getAddress($user->province, 'province');
				$user_district = $this->select->getAddress($user->district, 'district');
				$user_ward = $this->select->getAddress($user->ward, 'ward');

			    $user_province = $user_province->type .' '. $user_province->name;
			    $user_district = $user_district->type .' '. $user_district->name;
			    $user_ward = $user_ward->type .' '. $user_ward->name;
			    $user->full_address = $user->address.', '.$user_ward.', '.$user_district.', '.$user_province;
			}
		}
		
		return $user;
	}

	public function getUsers()
	{
		$users = $this->db->getData($this->table, $conditions, $offset, $limit);
		if (!$users) return false;
		foreach ($users as $key => $user) {
			if ($password) {
				unset($user->password);
				unset($user->salt);
				unset($user->verification_code);
				unset($user->activation);
			}
			$user->fullname = $user->last_name.' '.$user->first_name;
			$avatar = str_replace('profile/photo/', '', $user->avatar);
			$cover = str_replace('profile/cover/', '', $user->cover);

			$avatar_path = "/user/{$user->id}/profile/photo/"."larger_{$avatar}";
			$cover_path = "/user/{$user->id}/profile/cover/"."larger_{$cover}";
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
				if ($user->province && $user->district && $user->ward) {
					$user_province = $this->getAddress($user->province, 'province');
					$user_district = $this->getAddress($user->district, 'district');
					$user_ward = $this->getAddress($user->ward, 'ward');

				    $user_province = $user_province->type .' '. $user_province->name;
				    $user_district = $user_district->type .' '. $user_district->name;
				    $user_ward = $user_ward->type .' '. $user_ward->name;
				    $user->full_address = $user->address.', '.$user_ward.', '.$user_district.', '.$user_province;
				}
			}

			$users[$key] = $user;
		}
		if ($limit == 1) {
			return $users[0];
		}
		return $users;
	}

	public function save()
	{

	}
	
	// public function getUsers($conditions, $offset = 0, $limit = 10, $load_more = true, $getAddr = true)
	// {
		
	// }

}