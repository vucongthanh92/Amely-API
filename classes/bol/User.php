<?php

class User extends Object
{
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
	private $notify_token;


	public function __construct() 
	{
		parent::__construct();
		$this->table = "amely_users";
		$time = time();
		$this->insert = new stdClass;

		$created = $time;
		$this->time_created = $created;
		$this->data->time_created = $created;
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

}