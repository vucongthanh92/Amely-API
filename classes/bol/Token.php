<?php

class Token
{
	protected $token;
	protected $created;
	protected $expired;
	protected $user_guid;
	protected $session_id;

	public function initAttributes() {

	}

	public function save()
	{
		var_dump($this);
		die('1234');
		$db = SlimDatabase::getInstance();
		return $db->saveTable($object, "amely_usertokens", "insert", false);
	}
}