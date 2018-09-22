<?php

/**
* 
*/
class UserService extends Services
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
        $this->table = "amely_users";
    }

    public function getUserByType($input, $type ='id', $getAddr = true, $password = true)
	{	
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$user = $this->getUser($conditions, $getAddr, $password);
		if (!$user) return false;
		return $user;
	}

	public function getUser($conditions, $getAddr = true, $password = true)
	{
		$user = $this->searchObject($conditions, 0, 1);
		if (!$user) return false;
		$user = $this->changeStructureInfo($user,$getAddr,$password);
		return $user;
	}

	public function getUsers($conditions, $offset = 0, $limit = 10, $getAddr = true, $password = true)
	{
		$users = $this->searchObject($conditions, $offset, $limit);
		if (!$users) return false;
		foreach ($users as $key => $user) {
			$user = $this->changeStructureInfo($user,$getAddr,$password);
			$users[$key] = $user;
		}
		
		return array_values($users);
	}

	private function changeStructureInfo($user, $getAddr = true, $password = true)
	{
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
				$addressService = AddressService::getInstance();

				$user_province = $addressService->getAddress($user->province, 'province');
				$user_district = $addressService->getAddress($user->district, 'district');
				$user_ward = $addressService->getAddress($user->ward, 'ward');

			    $user_province = $user_province->type .' '. $user_province->name;
			    $user_district = $user_district->type .' '. $user_district->name;
			    $user_ward = $user_ward->type .' '. $user_ward->name;
			    $user->full_address = $user->address.', '.$user_ward.', '.$user_district.', '.$user_province;
			}
		}
		return $user;
	}

}