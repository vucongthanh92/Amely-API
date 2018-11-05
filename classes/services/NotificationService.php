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

    public function save(array $data)
    {
    	$notification = new Notification();
		$notification->data->owner_id = $data['owner_id'];
		$notification->data->type = $data['type'];
		$notification->data->title = $data['title'];
		$notification->data->description = $data['description'];
		$notification->data->from_id = $data['from_id'];
		$notification->data->from_type = $data['from_type'];
		$notification->data->subject_id = $data['subject_id'];
		$notification->data->subject_type = $data['subject_type'];
		$notification->data->item_id = $data['item_id'];
		$notification->data->viewed = 0;
		$notification_id = $notification->insert(true);
		if ($notification_id) {
			if ($data['notify_token']) {
				$notification->data->viewed = 1;
				$obj = new stdClass;
				$data['title'] = "AMELY";
				$obj->subject_id = (string) $data['subject_id'];
				$obj->subject_type = (string) $data['subject_type'];
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