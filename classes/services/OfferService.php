<?php

class OfferService extends Services
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
        $this->table = "amely_offers";
    }

	public function save($data)
	{
		if ($data['id']) return false;
		$time = time();
		$hour = $data["duration"]*24;
		$expried = strtotime("+{$hour} hours", $time);

		$offer = new Offer();
		foreach ($data as $key => $value) {
			$offer->data->$key = $value;
		}
		$offer->data->expried = $expried;
		$offer->data->status = 0;
		$offer_id = $offer->insert(true);
		if ($offer_id) {
			ItemService::getInstance()->updateStatus($data['item_id'], 0);
			$transactionService = TransactionService::getInstance();
			$transaction_data = null;
			$transaction_data['owner_id'] = $data['owner_id'];
			$transaction_data['type'] = 'user';
			$transaction_data['title'] = "";
			$transaction_data['description'] = "";
			$transaction_data['subject_type'] = 'offer';
			$transaction_data['subject_id'] = $offer_id;
			$transaction_data['status'] = 3;
			$transactionService->save($transaction_data);
			return $offer_id;
		}
		return false;
	}

	public function updateStatus($offer_id, $status, $counter_id = false)
    {
    	$transactionService = TransactionService::getInstance();
		$transaction_params = null;
    	$offer = $this->getOfferByType($offer_id);
    	$offer = object_cast("Offer", $offer);
    	$offer->data->status = $status;
		$offer->where = "id = {$offer_id}";
		if ($offer->update()) {
			switch ($status) {
				case 0:
					# code...
					break;
				case 1:
					$transaction_params['status'] = 5;
					if ($counter_id) {
						$transaction_params['subject_type'] = 'counter';
						$transaction_params['subject_id'] = $counter_id;
					}
					break;
				case 2:
					$transaction_params['status'] = 4;
					$transaction_params['subject_type'] = 'offer';
					$transaction_params['subject_id'] = $offer->id;
					break;
				default:
					# code...
					break;
			}
			
			$transaction_params['owner_id'] = $offer->owner_id;
			$transaction_params['type'] = 'user';
			$transaction_params['title'] = "";
			$transaction_params['description'] = "";
			
			
			return $transactionService->save($transaction_params);
		}
		return false;
    }

    public function getOfferByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$offer = $this->getOffer($conditions);
		if (!$offer) return false;
		return $offer;
    }

    public function getOffer($conditions)
	{
		$offer = $this->searchObject($conditions, 0, 1);
		if (!$offer) return false;
		$offer = $this->changeStructureInfo($offer);
		return $offer;
	}

	public function getOffers($conditions, $offset = 0, $limit = 10)
	{
		$offers = $this->searchObject($conditions, $offset, $limit);
		if (!$offers) return false;
		foreach ($offers as $key => $offer) {
			$offer = $this->changeStructureInfo($offer);
			$offers[$key] = $offer;
		}
		return array_values($offers);
	}

	private function changeStructureInfo($offer)
	{
		$loggedin_user = loggedin_user();
		$time = time();
		if ($offer->duration < 1) {
			$hour = $offer->duration*24;
			$time_end = strtotime("+{$hour} hours", $offer->time_created);
		} else {
			$time_end = strtotime("+{$offer->duration} days", $offer->time_created);
		}

		$offer->current_time = time();
		$offer->time_end = $time_end;
		if ($offer->owner_id == $loggedin_user->id) {
			$offer->offered = true;
		}
		return $offer;
	}
}