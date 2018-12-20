<?php

class RulePermission extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $permission_id;
    private $get;
    private $post;
    private $put;
    private $patch;
    private $delete;
    private $creator_id;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_rule_permission";
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