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
		$offer = $offerService->getOfferByType($data['owner_id'], 'id');
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
				$creator_id = $data['creator_id'];
				$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 7, $creator_id);
				$transactionService->save($transaction_params);
				break;
			case 1:
				$creator_id = $data['creator_id'];
				$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 10, $creator_id);
				$transactionService->save($transaction_params);

				$creator_id = $offer->owner_id;
				$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 5, $creator_id);
				$transactionService->save($transaction_params);
				break;
			case 2:
				$creator_id = $data['creator_id'];
				$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 9, $creator_id);
				$transactionService->save($transaction_params);

				$creator_id = $offer->owner_id;
				$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 6, $creator_id);
				$transactionService->save($transaction_params);
				break;
			case 3:
				$creator_id = $data['creator_id'];
				$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 8, $creator_id);
				$transactionService->save($transaction_params);
				break;
			default:
				break;
		}

		if ($offer->owner_id != $data['creator_id']) {
		    $notify_params = null;
			$notify_params['offer_id'] = $offer->id;
			$notify_params['counter_id'] = $counter_id;
			$notificationService->save($notify_params, 'counter:request');
		}
		return $counter_id;
	}

	public function updateStatus($counter_id, $status)
    {
    	$transactionService = TransactionService::getInstance();
    	$notificationService = NotificationService::getInstance();
    	$offerService = OfferService::getInstance();

    	$counter = $this->getCounterByType($counter_id, 'id');
    	$offer = $offerService->getOfferByType($counter->owner_id, 'id');
    	$counter = object_cast("Counter", $counter);
    	$counter->data->status = $status;
		$counter->where = "id = {$counter_id}";
		if ($counter->update()) {
			$notify_type = "";
			switch ($status) {
				case 0:
					# code...
					break;
				case 1:
					$notify_type = "counter:accept";
					$creator_id = $counter->creator_id;
					$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 10, $creator_id);
					$transactionService->save($transaction_params);

					$creator_id = $offer->owner_id;
					$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 5, $creator_id);
					$transactionService->save($transaction_params);
					break;
				case 2:
					$notify_type = "counter:reject";
					$creator_id = $counter->creator_id;
					$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 9, $creator_id);
					$transactionService->save($transaction_params);

					$creator_id = $offer->owner_id;
					$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 6, $creator_id);
					$transactionService->save($transaction_params);
					break;
				case 3:
					$creator_id = $counter->creator_id;
					$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'counter', $counter_id, 8, $creator_id);
					$transactionService->save($transaction_params);
					break;
				default:
					return false;
					break;
			}
			if ($notify_type) {
			    $notify_params = null;
				$notify_params['offer_id'] = $offer->id;
				$notify_params['counter_id'] = $counter_id;
				$notificationService->save($notify_params, $notify_type);
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