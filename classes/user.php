<?php

/**
* 
*/
class User extends SlimSelect
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function getUser($conditions, $offset = 0, $limit = 1, $load_more = true, $getAddr = true)
	{

	}
	
	// public function getUsers($conditions, $offset = 0, $limit = 10, $load_more = true, $getAddr = true)
	// {
		
	// }

}