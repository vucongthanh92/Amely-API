<?php

class Wallet extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $balance;
    private $currency;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_wallets";
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