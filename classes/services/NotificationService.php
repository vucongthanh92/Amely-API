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
    	$userService = UserService::getInstance();
		$str = "";
    	$notify_token = false;
    	switch ($notification_type) {
    		case 'order:reject':
    			$target = ORDER_REJECT;
				$owner_id = $data['to']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = 1;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['subject_id'];
				$description = $target." ".$data['display_order'];
    			break;
    		case 'order:approval':
    			$target = ORDER_APPROVAL;
				$owner_id = $data['to']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = 1;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['subject_id'];
				$description = $target." ".$data['display_order'];
    			break;
    		case 'order:request':
    			$target = ORDER_REQUEST;
				$owner_id = $data['to']->chain_store;
				$owner_type = 'store';
				$notify_token = $tokenService->getNotifyToken($data['to']->id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['subject_id'];
				$description = $target." ".$data['display_order'];
    			break;
    		case 'order:request:quickpay':
    			$target = ORDER_REQUEST_QUICKPAY;
				$owner_id = $data['to']->id;
				$owner_type = 'store';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['subject_id'];
				$description = $target." ".$data['display_order'];
    			break;
    		case 'comment:feed': 
    			$target = COMMENT_FEED;
				$owner_id = $data['to']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['subject_id'];
				$description = $data['from']->fullname." ".$target;
    			break;
    		case 'like:feed': 
    			$target = LIKE_FEED;
				$owner_id = $data['to']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['subject_id'];
				$description = $data['from']->fullname." ".$target;
    			break;
    		case 'like:shop': 
    			$target = LIKE_SHOP;
				$owner_id = $data['to']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'user';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$title = $data['from']->fullname;
				$subject_id = $data['subject_id'];
				$description = $data['from']->fullname." ".$target;
    			break;
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
				$description = $data['from']->fullname." ".$target;
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
				$description = $data['to']->fullname." ".$target;
    			break;
			case 'group:joined':
				$target = GROUP_JOINED;
				$owner_id = $to->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'group';
				$to_id = $data['to']->id;
				$to_type = 'user';
				$subject_id = $data['from']->id;
				$description = $target." ".$data['from']->title;
				break;
			case 'gift:request':
				$target = GIFT_REQUEST;
				$giftService = GiftService::getInstance();
				$gift = $giftService->getGiftByType($data['gift_id']);
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
				$description = $from_title." ".$target." ".$to_title;
				break;
			case 'gift:accept':
				$target = GIFT_ACCEPT;
				$giftService = GiftService::getInstance();
				$gift = $giftService->getGiftByType($data['gift_id']);
				if (!$gift) return false;
				$from = getInfo($gift->from_id, $gift->from_type);
				$to = getInfo($gift->to_id, $gift->to_type);
				$owner_id = $from['user']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $to['id'];
				$from_type = $to['type'];
				$from_title = $to['title'];
				$to_id = $from['id'];
				$to_type = $from['type'];
				$to_title = $from['title'];
				$subject_id = $gift->id;
				$description = $from_title." ".$target." ".$to_title;
				break;
			case 'gift:reject':
				$target = GIFT_REJECT;
				$giftService = GiftService::getInstance();
				$gift = $giftService->getGiftByType($data['gift_id']);
				if (!$gift) return false;
				$from = getInfo($gift->from_id, $gift->from_type);
				$to = getInfo($gift->to_id, $gift->to_type);
				$owner_id = $from['user']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $to['id'];
				$from_type = $to['type'];
				$from_title = $to['title'];
				$to_id = $from['id'];
				$to_type = $from['type'];
				$to_title = $from['title'];
				$subject_id = $gift->id;
				$description = $from_title." ".$target." ".$to_title;
				break;
			case 'counter:request':
				$target = COUNTER_REQUEST;
				$offerService = OfferService::getInstance();
				$counterService = CounterService::getInstance();
				$offer = $offerService->getOfferByType($data['offer_id'], 'id');
				$counter = $counterService->getCounterByType($data['counter_id'], 'id');
				$offer_owner = $userService->getUserByType($offer->owner_id, 'id');
				$counter_owner = $userService->getUserByType($counter->creator_id, 'id');
				$owner_id = $offer_owner->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $counter_owner->id;
				$from_type = 'user';
				$from_title = $counter_owner->fullname;
				$to_id = $offer_owner->id;
				$to_type = 'user';
				$to_title = $offer_owner->fullname;
				$subject_id = $offer->id;
				$description = $target." ".$from_title;
				break;
			case 'counter:accept':
				$target = COUNTER_ACCEPT;
				$offerService = OfferService::getInstance();
				$counterService = CounterService::getInstance();
				$offer = $offerService->getOfferByType($data['offer_id'], 'id');
				$counter = $counterService->getCounterByType($data['counter_id'], 'id');
				$offer_owner = $userService->getUserByType($offer->owner_id, 'id');
				$counter_owner = $userService->getUserByType($counter->creator_id, 'id');
				$owner_id = $counter_owner->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($counter_owner->id, $owner_type);
				$from_id = $offer_owner->id;
				$from_type = 'user';
				$from_title = $offer_owner->fullname;
				$to_id = $counter_owner->id;
				$to_type = 'user';
				$to_title = $counter_owner->fullname;
				$subject_id = $counter->id;
				$description = $target." ".$from_title;
				break;
			case 'counter:reject':
				$target = COUNTER_REJECT;
				$offerService = OfferService::getInstance();
				$counterService = CounterService::getInstance();
				$offer = $offerService->getOfferByType($data['offer_id'], 'id');
				$counter = $counterService->getCounterByType($data['counter_id'], 'id');
				$offer_owner = $userService->getUserByType($offer->owner_id, 'id');
				$counter_owner = $userService->getUserByType($counter->creator_id, 'id');
				$owner_id = $counter_owner->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($counter_owner->id, $owner_type);
				$from_id = $offer_owner->id;
				$from_type = 'user';
				$from_title = $offer_owner->fullname;
				$to_id = $counter_owner->id;
				$to_type = 'user';
				$to_title = $counter_owner->fullname;
				$subject_id = $counter->id;
				$description = $target." ".$from_title;
				break;
			case 'event:joined':
				$target = EVENT_JOINED;
				$eventService = EventService::getInstance();
				$userService = UserService::getInstance();
				$event = $eventService->getEventByType($data['from']->id, 'id');
				$owner_id = $data['to']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'event';
				$to_id = $event->id;
				$to_type = 'event';
				$subject_id = $event->id;
				$description = $data['from']->fullname." ".$target." ".$event->title;
				break;
			case 'event:invitation':
				$target = EVENT_INVITATION;
				$eventService = EventService::getInstance();
				$userService = UserService::getInstance();
				$event = $eventService->getEventByType($data['to']->id, 'id');
				$user = $userService->getUserByType($event->creator_id, 'id');
				$owner_id = $data['from']->id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $event->id;
				$from_type = 'event';
				$to_id = $data['from']->id;
				$to_type = 'user';
				$subject_id = $event->id;
				$description = $user->fullname." ".$target." ".$event->title;
				break;
			case 'event:approval':
				$target = EVENT_APPROVAL;
				$eventService = EventService::getInstance();
				$userService = UserService::getInstance();
				$event = $eventService->getEventByType($data['from']->id, 'id');
				$owner_id = $event->creator_id;
				$owner_type = 'user';
				$notify_token = $tokenService->getNotifyToken($owner_id, $owner_type);
				$from_id = $data['from']->id;
				$from_type = 'event';
				$to_id = $event->id;
				$to_type = 'event';
				$subject_id = $event->id;
				$description = $data['from']->fullname." ".$target." ".$event->title;
				break;
			default:
				return true;
				break;
    	}
    	
    	if ($from_id == $to_id && $from_type == 'user' && $to_type == 'user') return true;
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
				$obj->id = (string) $notification_id;
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

    public function getNotificationsByType($input, $type = 'user', $offset = 0, $limit = 10)
	{
		$conditions = null;
		$conditions[] = [
			'key' => 'type',
			'value' => "= '{$type}'",
			'operation' => ''
		];
		$conditions[] = [
			'key' => 'owner_id',
			'value' => "= '{$input}'",
			'operation' => 'AND'
		];
		$conditions[] = [
			'key' => 'from_id',
			'value' => "<> 0",
			'operation' => 'AND'
		];
		$conditions[] = [
			'key' => 'time_created',
			'value' => "DESC",
			'operation' => 'order_by'
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