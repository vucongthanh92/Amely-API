<?php

class CounterService extends Services
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
        $this->table = "amely_counter_offers";
    }

	public function save($data)
	{
		$transactionService = TransactionService::getInstance();
		$notificationService = NotificationService::getInstance();
		$counter = new Counter();
		foreach ($data as $key => $value) {
			$counter->data->$key = $value;
		}
	    $counter_id = $counter->insert(true);
	    if ($data['item_id']) {
	    	ItemService::getInstance()->updateStatus($data['item_id'], 0);
	    }
	    switch ($data['status']) {
			case 0:
				$transaction_params['status'] = 7;
				break;
			case 1:
				$transaction_params['status'] = 10;
				break;
			case 2:
				$transaction_params['status'] = 9;
				break;
			case 3:
				$transaction_params['status'] = 8;
			default:
				# code...
				break;
		}
		$transaction_params['owner_id'] = $data['creator_id'];
		$transaction_params['type'] = 'user';
		$transaction_params['title'] = "";
		$transaction_params['description'] = "";
		$transaction_params['subject_type'] = 'offer';
		$transaction_params['subject_id'] = $data['owner_id'];
		$transactionService->save($transaction_params);

	    $notify_params = null;
		$notify_params['offer_id'] = $data['offer_id'];
		$notify_params['counter_id'] = $counter_id;
		return response($notificationService->save($notify_params, 'counter:request'));
	}

	public function updateStatus($counter_id, $status)
    {
    	$transactionService = TransactionService::getInstance();
    	$counter = $this->getCounterByType($counter_id, 'id');
    	$counter = object_cast("Counter", $counter);
    	$counter->data->status = $status;
		$counter->where = "id = {$counter_id}";
		if ($counter->update()) {
			switch ($status) {
				case 0:
					# code...
					break;
				case 1:
					$transaction_params['status'] = 10;
					break;
				case 2:
					$transaction_params['status'] = 9;
					break;
				case 3:
					$transaction_params['status'] = 8;
					break;
				default:
					# code...
					break;
			}
			$transaction_params['owner_id'] = $counter->creator_id;
			$transaction_params['type'] = 'user';
			$transaction_params['title'] = "";
			$transaction_params['description'] = "";
			$transaction_params['subject_type'] = 'offer';
			$transaction_params['subject_id'] = $counter->owner_id;
			
			return $transactionService->save($transaction_params);
		}
    }

    public function getCounterByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$counter = $this->getCounter($conditions);
		if (!$counter) return false;
		return $counter;
    }

    public function getCounter($conditions)
	{
		$counter = $this->searchObject($conditions, 0, 1);
		if (!$counter) return false;
		return $counter;
	}

	public function getCounters($conditions, $offset = 0, $limit = 10)
	{
		$counters = $this->searchObject($conditions, $offset, $limit);
		if (!$counters) return false;
		return array_values($counters);
	}
}