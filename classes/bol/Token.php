<?php

class Token extends Object
{
	protected $table = "amely_usertokens";
	private $token;
	private $created;
	private $expired;
	private $user_guid;
	private $session_id;

	public function __construct() 
	{	
		parent::__construct();
		$time = time();
		$created = $time;
		$expired = $time+3600;
		$this->created = $created;
		$this->expired = $expired;
		$this->data->created = $created;
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
