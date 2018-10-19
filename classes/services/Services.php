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
		$obj->action = $action;
		$jsonData = json_encode($obj);
		fwrite($f, $jsonData);
		fclose($f);
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
		return true;
		if (!$message) return false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $settings['sms']);
		curl_setopt($ch, CURLOPT_POST, 1);
		$phone = preg_replace("/^0/i", "84", $mobile);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
            "sms=sms&message=$message&phone=$phone");
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
		$item = new InventoryItem;
		if (!$item->checkExist($item_guid, $owner_guid, $quantity_redeem)) return false;
		$code = md5(time().uniqid());

		$expired = time() + 300;
		$this->saveRedeemCode($item_guid, $code, $expired, $quantity_redeem, $guest_guid);
		$encrypt_code = ossn_encrypt_data($code);
		$code = Base64URLSafe::urlsafe_b64encode($encrypt_code); 
		return $code;
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

	// UPDATE amely_feeds SET description = REPLACE(description,',1',''), description = REPLACE(description,'1,',''),description = REPLACE(description,'1','') where id = 1;
}