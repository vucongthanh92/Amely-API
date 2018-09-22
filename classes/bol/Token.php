<?php

class Token extends Object
{
	private $token;
	private $time_created;
	private $expired;
	private $user_guid;
	private $session_id;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_usertokens";
		$expired = $this->data->time_created + 3600;
		$this->expired = $expired;
		$this->data->expired = $expired;
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
