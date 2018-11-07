<?php

class GiftService extends Services
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
        $this->table = "amely_gifts";
    }

	public function save($data)
	{
		$services = Services::getInstance();
		$userService = UserService::getInstance();
		$groupService = GroupService::getInstance();
		$obj = new stdClass;
		switch ($data['type']) {
			case 'user':
				$from = $userService->getUserByType($data['from_id'], 'id');
				$from->type = 'user';
				$obj->from = $from;
				break;
			case 'group':
				$from = $groupService->getGroupByType($data['from_id'], 'id');
				$from->type = 'group';
				$from->username = $from->id;
				$from->fullname = $from->title;
				$from->avatar = $from->avatar;
				$obj->from = $from;
				break;
			case 'event':
				# code...
				break;
			case 'business':
				# code...
				break;
			default:
				# code...
				break;
		}
		if (!$from) return false;
		

		switch ($data['to_type']) {
			case 'user':
				$to = $userService->getUserByType($data['to_id'], 'id');
				$to->type = 'user';
				$obj->to = $to;
				break;
			case 'group':
				$to = $groupService->getGroupByType($data['to_id'], 'id');
				$to->type = 'group';
				$to->username = $to->id;
				$to->fullname = $to->title;
				$to->avatar = $to->avatar;
				$obj->to = $to;
				break;
			case 'event':
				# code...
				break;
			case 'business':
				# code...
				break;
			default:
				# code...
				break;
		}
		if (!$to) return false;

		$gift = new Gift();
		$gift->data->from_id = $data['from_id'];
		$gift->data->from_type = $data['from_type'];
		$gift->data->to_id = $data['to_id'];
		$gift->data->to_type = $data['to_type'];
		$gift->data->item_id = $data['item_id'];
		$gift->data->message = $data['message'];
		$gift->data->status = 0;
		$gift_id = $gift->insert(true);
		if ($gift_id) {
			$item = new Item();
			$item->data->status = 0;
			$item->where = "id = {$data['item_id']}";
			$item->update();
			$obj->text = $data['message'];
			$media = new stdClass;
			$media->media_type = 'gift';
			$media->url = (string)$gift_id;
			$obj->attachment  = $media;
			$services->giftFB($obj);
			
			$notificationService = NotificationService::getInstance();
			$data = null;
			$data['gift_id'] = $gift_id;
			$notificationService->save($data, "gift:request");
			return true;
		}
		return false;
	}

    public function getGiftByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$gift = $this->getGift($conditions);
		if (!$gift) return false;
		return $gift;
    }

    public function getGift($conditions)
	{
		$offer = $this->searchObject($conditions, 0, 1);
		if (!$offer) return false;
		return $offer;
	}

	public function getGifts($conditions, $offset = 0, $limit = 10)
	{
		$offers = $this->searchObject($conditions, $offset, $limit);
		if (!$offers) return false;
		return array_values($offers);
	}
}