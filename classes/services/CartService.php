<?php

/**
* 
*/
class CartService extends Services
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
		
    }

    public function save()
    {
    	
    }

}
