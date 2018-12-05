<?php

class ProductGroup extends Object
{
    private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $percent;
	private $price;
	private $currency;
	private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_product_group";
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