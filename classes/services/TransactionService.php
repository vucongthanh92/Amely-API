<?php

/**
* 
*/
class TransactionService extends Services
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
        $this->table = "amely_transactions";
    }

    public function save($data)
    {
    	$conditions[] = [
    		'key' => 'owner_id',
    		'value' => "= '{$data['owner_id']}'",
    		'operation' => ''
    	];
    	$conditions[] = [
    		'key' => 'type',
    		'value' => "= '{$data['type']}'",
    		'operation' => 'AND'
    	];
    	$conditions[] = [
    		'key' => 'subject_id',
    		'value' => "= '{$data['subject_id']}'",
    		'operation' => 'AND'
    	];
    	$conditions[] = [
    		'key' => 'subject_type',
    		'value' => "= '{$data['subject_type']}'",
    		'operation' => 'AND'
    	];
    	$transaction = $this->getTransaction($conditions);
    	if ($transaction) return false;
    	$transaction = new Transaction();
    	foreach ($data as $key => $value) {
			$transaction->data->$key = $value;
		}
		return $transaction->insert();
    }

    public function getTransactionsByType($owner_id, $type, $subject_type, $offset = 0, $limit = 10)
	{
		$conditions = null;
		$conditions[] = [
			'key' => 'owner_id',
			'value' => "= '{$owner_id}'",
			'operation' => ''
		];
		$conditions[] = [
			'key' => 'type',
			'value' => "= '{$type}'",
			'operation' => 'AND'
		];
		$conditions[] = [
			'key' => 'subject_type',
			'value' => "= '{$subject_type}'",
			'operation' => 'AND'
		];
		$transactions = $this->getTransactions($conditions, $offset, $limit);
		if (!$transactions) return false;
		return $transactions;
	}

    public function getTransaction($conditions)
	{
		$transaction = $this->searchObject($conditions, 0, 1);
		if (!$transaction) return false;
		return $transaction;
	}

	public function getTransactions($conditions, $offset = 0, $limit = 10)
	{
		$transactions = $this->searchObject($conditions, $offset, $limit);
		if (!$transactions) return false;
		return array_values($transactions);
	}

}