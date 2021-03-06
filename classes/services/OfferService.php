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
		$transactionService = TransactionService::getInstance();

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
			$creator_id = $data['owner_id'];
			$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', 'offer', $offer_id, 3, $creator_id);
			$transactionService->save($transaction_params);

			return $offer_id;
		}
		return false;
	}

	public function updateStatus($offer_id, $status, $counter_id = false)
    {
    	$transactionService = TransactionService::getInstance();

    	$offer = $this->getOfferByType($offer_id);
    	$offer = object_cast("Offer", $offer);
    	$offer->data->status = $status;
		$offer->where = "id = {$offer_id}";

		$status = $subject_type = $subject_id = null;
		if ($offer->update()) {
			switch ($status) {
				case 0:
					# code...
					break;
				case 1:
					$status = 5;

					if ($counter_id) {
						$subject_type = 'counter';
						$subject_id = $counter_id;
					}
					break;
				case 2:
					$status = 4;
					$subject_type = 'offer';
					$subject_id = $offer->id;
					break;
				default:
					# code...
					break;
			}

			$creator_id = $data['owner_id'];
			$transaction_params = $transactionService->getTransactionParams($creator_id, 'user', '', '', $subject_type, $subject_id, $status, $creator_id);
			$transactionService->save($transaction_params);
			
			return true;
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