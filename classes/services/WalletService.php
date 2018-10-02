<?php

class WalletService extends Services
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
        $this->table = "amely_wallets";
    }

    public function getWalletByOwnerId($owner_id)
    {
    	$conditions = null;
    	$conditions[] = [
    		'key' => 'owner_id',
    		'value' => "= {$owner_id}",
    		'operation' => ''
    	];
    	$wallet = $this->searchObject($conditions, 0, 1);
		if (!$wallet) return false;
		return $wallet;
    }

}