<?php

/**
* 
*/
class NotificationService extends Services
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
        $this->table = "amely_notifications";
    }

    public function save($data, $notification_type)
    {
    	$tokenService = TokenService::getInstance();
		$str = "";
    	$notify_token = false;
    	switch ($notification_type) {
    		case 'friend:invitation':
    			$target = INVITATION_FRIEND;
				$owner_id = $data['to']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['from']->id;
    			break;
    		case 'friend:approval':
    			$target = APPROVAL_FRIEND;
				$owner_id = $to->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['from']->id;
    			break;
			case 'group:invite':
				$target = GROUP;
				$user = $userService->getUserByType($to->owner_id, 'id', false);
				if (!$user) return false;
				$owner_id = $user->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $to->id;
				$from_type = 'group';
				break;
			case 'event:invite':
				$target = EVENT;
				break;
			case 'gift:request':
				$target = GIFT_REQUEST;
				$giftService = GiftService::getInstance();
				$gift = $giftService->getGiftByType($data['gift']);
				if (!$gift) return false;
				$from = getInfo($gift->from_id, $gift->from_type);
				$to = getInfo($gift->to_id, $gift->to_type);
				$owner_id = $to['user']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $from['id'];
				$from_type = $from['type'];
				$from_title = $from['title'];
				$to_id = $to['id'];
				$to_type = $to['type'];
				$to_title = $to['title'];
				$subject_id = $gift->id;
				break;
			case 'gift:accept':
				$target = GIFT_ACCEPT;
				$giftService = GiftService::getInstance();
				$gift = $giftService->getGiftByType($data['gift']);
				if (!$gift) return false;
				$from = getInfo($gift->from_id, $gift->from_type);
				$to = getInfo($gift->to_id, $gift->to_type);
				$owner_id = $to['user']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $from['id'];
				$from_type = $from['type'];
				$from_title = $from['title'];
				$to_id = $to['id'];
				$to_type = $to['type'];
				$to_title = $to['title'];
				$subject_id = $gift->id;
				break;
			case 'gift:reject':
				$target = GIFT_REJECT;
				$giftService = GiftService::getInstance();
				$gift = $giftService->getGiftByType($data['gift']);
				if (!$gift) return false;
				$from = getInfo($gift->from_id, $gift->from_type);
				$to = getInfo($gift->to_id, $gift->to_type);
				$owner_id = $to['user']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $from['id'];
				$from_type = $from['type'];
				$from_title = $from['title'];
				$to_id = $to['id'];
				$to_type = $to['type'];
				$to_title = $to['title'];
				$subject_id = $gift->id;
				break;
			
			default:
				return response(true);
				break;
    	}
    	
    	$description = $from_title." ".$target." ".$to_title;
    	$notification = new Notification();
		$notification->data->owner_id = $owner_id;
		$notification->data->type = $owner_type;
		$notification->data->title = "AMELY";
		$notification->data->description = $description;
		$notification->data->from_id = $from_id;
		$notification->data->from_type = $from_type;
		$notification->data->to_id = $to_id;
		$notification->data->to_type = $to_type;
		$notification->data->subject_id = $subject_id;
		$notification->data->subject_type = $notification_type;
		$notification->data->item_id = "";
		$notification->data->viewed = 0;
		$notification_id = $notification->insert(true);
		if ($notification_id) {
			if ($notify_token) {
				$notification = new Notification();
				$notification->data->viewed = 1;
				$notification->where = "id = {$notification_id}";
				$notification->update();

				$obj = new stdClass;
				$data = null;
				$data['notify_token'] = $notify_token;
				$data['title'] = "AMELY";
				$data['description'] = $description;
				$obj->subject_id = (string) $subject_id;
				$obj->subject_type = (string) $notification_type;
				$obj->notification_id = (string) $notification_id;
				$data['data'] = $obj;
				Services::getInstance()->notify($data);
			}
		}
		return true;
    }

    public function viewed($id)
    {
    	if (!$id) return false;
    	$notification = Notification();
    	$notification->data->viewed = 1;
    	$notification->where = "id = {$id}";
    	return $notification->update();
    }

    public function getNotificationsByType($input, $type = 'id', $offset = 0, $limit = 10)
	{
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$notifications = $this->searchObject($conditions, $offset, $limit);
		if (!$notifications) return false;
		return array_values($notifications);
	}

    public function getNotification($conditions)
	{
		$notification = $this->searchObject($conditions, 0, 1);
		if (!$notification) return false;
		return $notification;
	}

	public function getNotifications($conditions, $offset = 0, $limit = 10)
	{
		$notifications = $this->searchObject($conditions, $offset, $limit);
		if (!$notifications) return false;
		return array_values($notifications);
	}
}