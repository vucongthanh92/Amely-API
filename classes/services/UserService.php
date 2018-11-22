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

    public function save($data)
    {
    	$user = new User();
    	foreach ($data as $key => $value) {
    		$user->data->$key = $value;
    	}
    	if ($data['id']) {
    		$user->where = "id = {$data['id']}";
    		return $user->update(true);
    	} else {
    		$user_id = $user->insert(true);

    		$walletService = WalletService::getInstance();
			$walletService->save($user_id);
			$inventoryService = InventoryService::getInstance();
			$inventoryService->save($user_id, 'user', $user_id);
    		return $user_id;
    	}
    }

    public function login($username)
    {
    	$time = time();
    	$user = new User();
    	$user->data->last_login = $time;
    	$user->where = "username = '{$username}'";
    	if ($user->update()) {
    		$obj = new stdClass;
    		$obj->username = $username;
    		$obj->last_login = $time;
    		$this->connectServer("login", $obj);
    		return true;
    	}
    	return false;
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

	public function getUsersByType($input, $type ='id', $getAddr = true, $password = true)
	{	
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ($input)",
			'operation' => ''
		];
		$users = $this->getUsers($conditions, 0, 999999999, $getAddr, $password);
		if (!$users) return false;
		return $users;
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
		$addressService = AddressService::getInstance();
		$imageService = ImageService::getInstance();

		if ($password) {
			unset($user->password);
			unset($user->salt);
			unset($user->verification_code);
			unset($user->activation);
		}
		$user->fullname = $user->last_name.' '.$user->first_name;

		$user->avatar = $imageService->showAvatar($user->id, $user->avatar, 'user', 'large');
		$user->cover = $imageService->showCover($user->id, $user->cover, 'user', 'large');

		if ($getAddr) {
			if ($user->province && $user->district && $user->ward) {
				$user_province = $addressService->getAddress($user->province, 'province');
				$user_district = $addressService->getAddress($user->district, 'district');
				$user_ward = $addressService->getAddress($user->ward, 'ward');

			    $user->province_name = $user_province->name;
			    $user->district_name = $user_district->name;
			    $user->ward_name = $user_ward->name;
			    $user_province = $user_province->type .' '. $user_province->name;
			    $user_district = $user_district->type .' '. $user_district->name;
			    $user_ward = $user_ward->type .' '. $user_ward->name;

			    $user->full_address = $user->address.', '.$user_ward.', '.$user_district.', '.$user_province;
			}
		}
		return $user;
	}

}