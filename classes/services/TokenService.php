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

    public function save($token_code, $user_id, $type)
    {	
    	$token = $this->getTokenByType($user_id, $type);
    	if ($token) {
    		$token = object_cast("Token", $token);
    		$token->data->token = $token_code;
    		$token->where = "id = {$token->id}";
    		return $token->update();
    	} else {
	    	$token = new Token();
	        $token->data->token = $token_code;
			$token->data->user_id = $user_id;
			$token->data->session_id = session_id();
			$token->data->type = $type;
			return $token->insert();
    	}
    }

    public function updateNotifyToken($notify_token, $user_id, $type)
    {
    	$token = new Token();
    	$token->data->notify_token = $notify_token;
    	$token->where = "user_id = {$user_id} AND type = '{$type}'";
    	return $token->update();
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

	public function getTokenByType($user_id, $type)
	{
		$conditions[] = [
			'key' => 'user_id',
			'value' => "= {$user_id}",
			'operation' => ''
		];

		$conditions[] = [
			'key' => 'type',
			'value' => "= '{$type}'",
			'operation' => 'AND'
		];

		$token = $this->searchObject($conditions, 0, 1);
		if (!$token) return false;
		return $token;
	}

	public function getToken($conditions)
	{
		$token = $this->searchObject($conditions, 0, 1);
		if (!$token) return false;
		return $token;
	}
}