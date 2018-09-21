<?php

class User extends Object
{
	protected $table = "amely_users";
	private $type;
	private $username;
	private $email;
	private $password;
	private $salt;
	private $first_name;
	private $last_name;
	private $last_login;
	private $last_activity;
	private $activation;
	private $time_created;
	private $verification_code;
	private $mobilelogin;
	private $birthdate;
	private $gender;
	private $usercurrency;
	private $province;
	private $district;
	private $ward;
	private $address;
	private $friends_hidden;
	private $birthdate_hidden;
	private $mobile_hidden;
	private $language;
	private $chain_store;
	private $avatar;
	private $cover;
	private $gift_count;
	private $offer_count;
	private $blockedusers;


	public function __construct() 
	{
		parent::__construct();
		$time = time();
		$this->insert = new stdClass;
		$activation = md5(time() . rand());

		$created = $time;
		$this->time_created = $created;
		$this->activation = $activation;

		$this->data->created = $created;
		$this->data->activation = $activation;
	}

	public function __set($key, $value)
    {
        if (property_exists($this, $key)) {
        	$this->$key = $value;
        }
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
    }

    public function getUser($conditions, $getAddr = true, $password = true)
	{
		$users = $this->db->getData($this->table, $conditions,0,1);
		var_dump($users);
		die('123');
		if (!$users) return false;
		$user = $users[0];
		var_dump($user);
		die('123213');
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
		
		return $this;
	}

}