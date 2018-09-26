<?php

class TokenService extends Services
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
        $this->table = "amely_usertokens";
    }

    public function checkToken($token)
	{
		$conditions = null;
		$conditions[] = [
			'key' => 'token',
			'value' => "= '{$token}'",
			'operation' => ""
		];
		$token = $this->searchObject($conditions, 0, 1);
		if ($token) {
			session_id($token->session_id);
			session_reset();
			if ($token->token != $_SESSION["TOKEN"]) {
				$userService = UserService::getInstance();

				$user = $userService->getUserByType($token->user_id, 'id', true);
			    
			    $_SESSION["OSSN_USER"] = $user;
			}
			return true;
		}
		return false;
	}
}