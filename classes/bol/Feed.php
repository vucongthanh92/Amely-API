<?php

class Feed extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $location;
    private $tag;
    private $mood_id;
    private $poster_id;
    private $privacy;
    private $item_type;
    private $item_id;
    private $images;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_feeds";
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