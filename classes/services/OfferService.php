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
		$time = time();
		$hour = $data["duration"]*24;
		$expried = strtotime("+{$hour} hours", $time);

		$offer = new Offer();
		$offer->data->owner_id = $data['owner_id'];
		$offer->data->type = 'user';
		$offer->data->title = "";
		$offer->data->description = "";
		$offer->data->target = $data['target'];
		$offer->data->duration = $data['duration'];
		$offer->data->offer_type = $data['offer_type'];
		$offer->data->expried = $expried;
		$offer->data->status = 0;
		$offer->data->option = $data['option'];
		$offer->data->limit_counter = $data['limit_counter'];
		$offer->data->item_id = $data['item_id'];
		$offer->data->note = $data['note'];
		$offer_id = $offer->insert(true);
		if ($offer_id) {
			$item = new Item();
			$item->data->status = 0;
			$item->where = "id = {$data['item_id']}";
			$item->update();
			return $offer_id;
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