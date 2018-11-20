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
		$offerService = OfferService::getInstance();
		$offer = $offerService->getOfferByType($data['offer_id'], 'id');
		$counter = new Counter();
		foreach ($data as $key => $value) {
			$counter->data->$key = $value;
		}
		$counter->data->type = "offer";
	    $counter_id = $counter->insert(true);
	    if ($data['item_id']) {
	    	ItemService::getInstance()->updateStatus($data['item_id'], 0);
	    }
	    switch ($data['status']) {
			case 0:
				$transaction_params['status'] = 7;
				$transaction_params['owner_id'] = $data['creator_id'];
				$transaction_params['type'] = 'user';
				$transaction_params['title'] = "";
				$transaction_params['description'] = "";
				$transaction_params['subject_type'] = 'counter';
				$transaction_params['subject_id'] = $counter_id;
				$transactionService->save($transaction_params);
				break;
			case 1:
				$transaction_params['status'] = 10;
				$transaction_params['owner_id'] = $data['creator_id'];
				$transaction_params['type'] = 'user';
				$transaction_params['title'] = "";
				$transaction_params['description'] = "";
				$transaction_params['subject_type'] = 'counter';
				$transaction_params['subject_id'] = $counter_id;
				$transactionService->save($transaction_params);

				$transaction_params['status'] = 5;
				$transaction_params['owner_id'] = $offer->owner_id;
				$transaction_params['type'] = 'user';
				$transaction_params['title'] = "";
				$transaction_params['description'] = "";
				$transaction_params['subject_type'] = 'counter';
				$transaction_params['subject_id'] = $counter_id;
				$transactionService->save($transaction_params);
				break;
			case 2:
				$transaction_params['status'] = 9;
				$transaction_params['owner_id'] = $data['creator_id'];
				$transaction_params['type'] = 'user';
				$transaction_params['title'] = "";
				$transaction_params['description'] = "";
				$transaction_params['subject_type'] = 'counter';
				$transaction_params['subject_id'] = $counter_id;
				$transactionService->save($transaction_params);

				$transaction_params['status'] = 6;
				$transaction_params['owner_id'] = $offer->owner_id;
				$transaction_params['type'] = 'user';
				$transaction_params['title'] = "";
				$transaction_params['description'] = "";
				$transaction_params['subject_type'] = 'counter';
				$transaction_params['subject_id'] = $counter_id;
				$transactionService->save($transaction_params);
				break;
			case 3:
				$transaction_params['status'] = 8;
				$transaction_params['owner_id'] = $data['creator_id'];
				$transaction_params['type'] = 'user';
				$transaction_params['title'] = "";
				$transaction_params['description'] = "";
				$transaction_params['subject_type'] = 'counter';
				$transaction_params['subject_id'] = $counter_id;
				$transactionService->save($transaction_params);
			default:
				# code...
				break;
		}

	    $notify_params = null;
		$notify_params['offer_id'] = $data['offer_id'];
		$notify_params['counter_id'] = $counter_id;
		$notificationService->save($notify_params, 'counter:request');
		return $counter_id;
	}

	public function updateStatus($counter_id, $status)
    {
    	$transactionService = TransactionService::getInstance();
    	$offerService = OfferService::getInstance();
    	$counter = $this->getCounterByType($counter_id, 'id');
    	$offer = $offerService->getOfferByType($counter->owner_id, 'id');
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
					$transaction_params['owner_id'] = $counter->creator_id;
					$transaction_params['type'] = 'user';
					$transaction_params['title'] = "";
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'counter';
					$transaction_params['subject_id'] = $counter_id;
					$transactionService->save($transaction_params);

					$transaction_params['status'] = 5;
					$transaction_params['owner_id'] = $offer->owner_id;
					$transaction_params['type'] = 'user';
					$transaction_params['title'] = "";
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'counter';
					$transaction_params['subject_id'] = $counter_id;
					$transactionService->save($transaction_params);
					break;
				case 2:
					$transaction_params['status'] = 9;
					$transaction_params['owner_id'] = $counter->creator_id;
					$transaction_params['type'] = 'user';
					$transaction_params['title'] = "";
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'counter';
					$transaction_params['subject_id'] = $counter_id;
					$transactionService->save($transaction_params);

					$transaction_params['status'] = 6;
					$transaction_params['owner_id'] = $offer->owner_id;
					$transaction_params['type'] = 'user';
					$transaction_params['title'] = "";
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'counter';
					$transaction_params['subject_id'] = $counter_id;
					$transactionService->save($transaction_params);
					break;
				case 3:
					$transaction_params['status'] = 8;
					$transaction_params['owner_id'] = $counter->creator_id;
					$transaction_params['type'] = 'user';
					$transaction_params['title'] = "";
					$transaction_params['description'] = "";
					$transaction_params['subject_type'] = 'counter';
					$transaction_params['subject_id'] = $counter_id;
					$transactionService->save($transaction_params);
					break;
				default:
					return false;
					break;
			}
			return true;
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