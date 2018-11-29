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

    public function save($owner_id)
    {
	    $wallet = new Wallet();
		$wallet->data->owner_id = $owner_id;
		$wallet->data->type = 'user';
		$wallet->data->title = "";
		$wallet->data->description = "";
		$wallet->data->balance = 0;
		$wallet->data->currency = "VND";
		return $wallet->insert(true);
    }

    public function deposit($owner_id, $total, $status, $subject_id, $subject_type)
    {
    	$transactionService = TransactionService::getInstance();
    	$wallet = $this->getWalletByOwnerId($owner_id);
        
        $wallet = object_cast("Wallet", $wallet);
    	$wallet->data->balance = $wallet->balance + $total;
        $wallet->data->id = $wallet->id;
    	$wallet->where = "id = {$wallet->id}";
        if ($wallet->update(true)) {
    		$transaction_params['owner_id'] = $wallet->id;
			$transaction_params['type'] = 'wallet';
			$transaction_params['title'] = $total;
			$transaction_params['description'] = "";
			$transaction_params['subject_type'] = $subject_type;
			$transaction_params['subject_id'] = $subject_id;
			$transaction_params['status'] = $status;
			$transactionService->save($transaction_params);
    		return true;
    	}
    	return false;
    }

    public function withdraw($owner_id, $total, $status, $subject_id, $subject_type)
    {
        $transactionService = TransactionService::getInstance();
    	$wallet = $this->getWalletByOwnerId($owner_id);
        $wallet = object_cast("Wallet", $wallet);
    	$wallet->data->balance = $wallet->balance - $total;
        $wallet->data->id = $wallet->id;
    	$wallet->where = "id = {$wallet->id}";
    	if ($wallet->update(true)) {
    		$transaction_params['owner_id'] = $wallet->id;
			$transaction_params['type'] = 'wallet';
			$transaction_params['title'] = $total;
			$transaction_params['description'] = "";
			$transaction_params['subject_type'] = $subject_type;
            $transaction_params['subject_id'] = $subject_id;
			$transaction_params['status'] = $status;
			$transactionService->save($transaction_params);
    		return true;	
    	}
    	return false;
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