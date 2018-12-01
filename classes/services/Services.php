<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Services extends SlimDatabase
{
	protected static $instance = null;
	protected $table;

    public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function searchObject($conditions, $offest, $limit)
    {
    	if (!$this->table) return false;
    	$result = $this->getData($this->table, $conditions, $offest, $limit);
    	if (!$result) return false;
    	if ($limit == 1) {
    		return $result[0];
    	}
    	return $result;
    }

    public function connectServer($action, $obj)
    {
    	global $settings;
		$f = fsockopen($settings['nodejs']['host'], $settings['nodejs']['port'], $errno, $errstr, 30);
		if ($f) {
			$obj->action = $action;
			$jsonData = json_encode($obj);
			fwrite($f, $jsonData);
			fclose($f);
		}
		return true;
    }

	public function saveFirebase($path, $params)
	{
		$firebase = new \Geckob\Firebase\Firebase(FIREBASE_KEY);
		$firebase = $firebase->setPath($path);
		if ($params) {
			foreach ($params as $key => $value) {
				$firebase->set($key, (string)$value);
			}
		}
		return true;
	}

	public function sendByMobile($mobile, $message = false)
	{
		global $settings;
		if (!$message) return false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $settings['sms']);
		curl_setopt($ch, CURLOPT_POST, 1);
		$phone = preg_replace("/^0/i", "84", $mobile);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
            "sms=true&message={$message}&phone={$phone}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		curl_close ($ch);
		return true;
	}

	public function sendByEmail($email, $subject, $body)
	{
		global $mail;
		$phpMailer = new PHPMailer(true);
		try {
		    $phpMailer->SMTPDebug = $mail->mail_SMTPDebug;
		    $phpMailer->isSMTP();
		    $phpMailer->Host = $mail->mail_Host;
		    $phpMailer->SMTPAuth = $mail->mail_SMTPAuth;
		    $phpMailer->Username = $mail->mail_Username;
		    $phpMailer->Password = $mail->mail_Password;
		    $phpMailer->SMTPSecure = $mail->mail_SMTPSecure;
		    $phpMailer->Port = $mail->mail_Port;
		    $phpMailer->setFrom($mail->mail_From, $mail->mail_Sitename);
		    $phpMailer->addAddress($email);     // Add a recipient

		    //Content
		    $phpMailer->isHTML(true);                                  // Set email format to HTML
		    $phpMailer->Subject = $subject;
		    $phpMailer->Body    = $body;

		    $phpMailer->send();
		    return true;
		} catch (Exception $e) {
			return false;
		}
		return false;
	}

	public function recurse_copy($source,$dest)
	{ 
		if (!is_dir($source)) mkdir($source, 0777, true);
		if (!is_dir($dest)) mkdir($dest, 0777, true);
		
		foreach (
			$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST) as $item
		) {
			if ($item->isDir()) {
				mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			} else {
				copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			}
		}

		return true;
	}

	public function generateRedeemCode($item_id, $quantity_redeem, $owner_id, $guest_id)
	{
		
	}

	public function connectServerGHTK($token, $url, $data, $method = "GET", $return_transfer = true, $version = CURL_HTTP_VERSION_1_1)
	{
		$curl = curl_init();
		switch ($method) {
			case 'GET':
		        curl_setopt_array($curl, array(
		            CURLOPT_URL => $url . http_build_query($data),
		            CURLOPT_RETURNTRANSFER => $return_transfer,
		            CURLOPT_HTTP_VERSION => $version,
		            CURLOPT_HTTPHEADER => array(
		                "Token: " .$token,
		            ),
		        ));
				break;
			case 'POST':
				curl_setopt_array($curl, array(
		            CURLOPT_URL => $url,
		            CURLOPT_RETURNTRANSFER => $return_transfer,
		            CURLOPT_HTTP_VERSION => $version,
		            CURLOPT_CUSTOMREQUEST => "POST",
		            CURLOPT_POSTFIELDS => $data,
		            CURLOPT_HTTPHEADER => array(
		                "Content-Type: application/json",
		                "Token: " .$token,
		                "Content-Length: " . strlen($data),
		            ),
		        ));
				break;
			default:
				# code...
				break;
		}

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        return $response;
	}

	public function encrypt($input)
	{
		$ossnCrypto = OssnCrypto::getInstance();
		$encrypt = $ossnCrypto->encrypt($input);
		return $encrypt;
	}

	public function decrypt($input)
	{
		$ossnCrypto = OssnCrypto::getInstance();
		$decrypt = $ossnCrypto->decrypt($input);
		return $decrypt;
	}

	public function b64encode($input)
	{
		$code = \MIME\Base64URLSafe::urlsafe_b64encode($input); 
		return $code;
	}

	public function b64decode($input)
	{
		$code = \MIME\Base64URLSafe::urlsafe_b64decode($input); 
		return $code;
	}

	public function giftFB($obj)
	{
		return $this->connectServer("gift", $obj);
	}

	public function memberGroupFB($group_id, $member_username, $type = 'add')
	{
		$member = new stdClass;
		$member->username = $member_username;
		$obj = new stdClass;
		$obj->type = $type;
		$obj->member = $member;
		$obj->group_id = $group_id;
		return $this->connectServer("memberGroup", $obj);
	}

	public function deleteGroupFB($owner_username, $group_id, $group_title)
	{
		$obj = new stdClass;
		$obj->group_id = $group_id;
		return $this->connectServer("deleteGroup", $obj);
	}
	
	public function createGroupFB($owner_username, $group_id, $group_title)
	{
		global $settings;
		$owner = new stdClass;
		$owner->username = $owner_username;
		$obj = new stdClass;
		$obj->owner = $owner;
		$obj->group_id = $group_id;
		$obj->title = $group_title;
		$obj->group_avatar = $settings['image']['avatar'];
		return $this->connectServer("createGroup", $obj);
	}

	public function addFriendFB($from, $to)
	{
		$obj = new stdClass;
		$obj->from = $from;
		$obj->from_fullname = $from->fullname;
		$obj->from_avatar = $from->avatar;
		$obj->to = $to;
		$obj->to_fullname = $to->fullname;
		$obj->to_avatar = $to->avatar;
		return $this->connectServer("addFriend", $obj);
	}

	public function notify($params)
	{
		$obj = new stdClass;
		$obj->token = $params['notify_token'];
		$obj->title = $params['title'];
		$obj->body = $params['description'];
		$obj->data = (Object) $params['data'];
		return $this->connectServer("notify", $obj);
	}

	public function downloadImage($owner_id, $owner_type, $image_type, $images)
	{
		global $settings;
		$imageService = ImageService::getInstance();
		if (!in_array($image_type, ['avatar','cover','images'])) return response(false);
		

		$path = DIRECTORY_SEPARATOR."{$owner_type}".DIRECTORY_SEPARATOR."{$owner_id}".DIRECTORY_SEPARATOR."{$image_type}";
		$dir = $settings['image']['path'].$path;
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}
		array_map('unlink', array_filter((array) glob("{$dir}/*")));

		$filenames = [];

		foreach ($images as $key => $url) {
			$filename = md5($url . rand() . time()) . '.jpg';
			array_push($filenames, $filename);
			$image = $dir.DIRECTORY_SEPARATOR.$filename;

			file_put_contents($image, fopen($url, 'r'));

			$sizes = $imageService->image_sizes();
			foreach ($sizes as $key => $size) {
				$resize = new ResizeImage($image);
				$resize->resizeTo($size, $size, 'maxWidth');
				$resize->saveImage(DIRECTORY_SEPARATOR."{$dir}".DIRECTORY_SEPARATOR."{$key}_{$filename}");
			}
			unlink($image);
		}

		$filenames = implode(',', $filenames);

		switch ($owner_type) {
			case 'feed':
				$feed = new Feed();
				$feed->data->images = $filenames;
				$feed->where = "id = {$owner_id}";
				return response($feed->update());
			case 'user':
				$user = new User();
				$user->data->$image_type = $filenames;
				$user->where = "id = {$owner_id}";
				return response($user->update());
				break;
			case 'comment':
				$comment = new Annotation();
				$comment->data->images = $filenames;
				$comment->where = "id = {$owner_id}";
				return response($comment->update());
				break;
			case 'group':
				$group = new Group();
				$group->data->$image_type = $filenames;
				$group->where = "id = {$owner_id}";
				return response($group->update());
			default:
				# code...
				break;
		}
	}

	// UPDATE amely_feeds SET description = REPLACE(description,',1',''), description = REPLACE(description,'1,',''),description = REPLACE(description,'1','') where id = 1;
}