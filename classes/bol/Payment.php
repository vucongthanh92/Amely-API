<?php

class Payment extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $payment_method;
    private $request;
    private $response;
    private $options;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_payment";
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