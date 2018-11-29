<?php
namespace Amely\Payment\OnePay;

class OPATM extends \Object implements \Amely\Payment\IPaymentMethod
{
	// AVS_City show City in Onepay
	// AVS_StateProv show Province in Onepay
	// AVS_PostCode show Postcode in Onepay
	// AVS_Country show Country in Onepay
	public $virtualPaymentClientURL;
	public $vpc_Merchant;
	public $vpc_AccessCode;
	public $secure_secret;
	public $vpc_Version;
	public $vpc_Command;
	public $vpc_Locale;
	public $vpc_TicketNo;
	public $display;


	public $order_id;
	public $description;
	public $amount;
	public $creator;
	public $order_type;
	public $payment_method;
	public $duration;
	public $payment_id;
	public $shipping_method;
	public $options;

	function __construct()
	{
		$this->Title = "";
		$this->virtualPaymentClientURL = "https://mtf.onepay.vn/onecomm-pay/vpc.op";
		$this->vpc_Merchant = "ONEPAY";
		$this->vpc_AccessCode = "D67342C2";
		$this->secure_secret = 'A3EFDFABA8653DF2342E8DAC29B51AF0';
		$this->vpc_Version = '2';
		$this->vpc_Command = 'pay';
		$this->vpc_Locale = 'vn';
		$this->vpc_TicketNo = getRealIpAddr();
		$this->display = 'mobile';
		// $this->vpc_ReturnURL = $return_url;
		// $this->AgainLink = $return_url;
	}

	public function process()
	{
		global $settings;

		$order_id = $this->order_id;
		$order_type = $this->order_type;
		$creator = $this->creator;
		switch ($order_type) {
			case 'HD':
				$purchaseOrderService = \PurchaseOrderService::getInstance();
				$order = $purchaseOrderService->getPOByType($order_id);
				$display_order = convertPrefixOrder($order_type, $order->id, $order->time_created);
				break;
			case 'WALLET':
				$display_order = convertPrefixOrder($order_type, $order_id, time());
				break;
			default:
				$options = null;
				break;
		}
		$options = serialize($this->options);
		$paymentsService = \PaymentsService::getInstance();
		$payment_id = $paymentsService->save($order_id, $this->order_type, $this->payment_method, $options);
		$return_url = $settings['url'].$settings['prefix'].'/payment_response?payment_id='.$payment_id;

		$country = "VN";
		$amout = $this->amount*100;
		$address = $creator->address;
		$province = $creator->province_name;
		$district = $creator->district_name;
		$email = $creator->email;
		$username = $creator->username;
		$mobilelogin = $creator->mobilelogin;
		$vpcURL = $this->virtualPaymentClientURL . "?";
		unset($this->virtualPaymentClientURL);
		$stringHashData = "";
		$time = time();
		$requests = [
			"Title" => $username,
			"vpc_AccessCode" => $this->vpc_AccessCode,
			"vpc_Amount" => $amout,
			"vpc_Command" => $this->vpc_Command,
			"vpc_Currency" => "VND",
			"vpc_Customer_Email" => $email,
			"vpc_Customer_Id" => $username,
			"vpc_Customer_Phone" => $mobilelogin,
			"vpc_Locale" => $this->vpc_Locale,
			"vpc_MerchTxnRef" => $display_order,
			"vpc_Merchant" => $this->vpc_Merchant,
			"vpc_OrderInfo" => $display_order,
			"vpc_ReturnURL" => $return_url,
			"vpc_SHIP_City" => $province,
			"vpc_SHIP_Country" => $country,
			"vpc_SHIP_Provice" => $district,
			"vpc_SHIP_Street01" => $address,
			"vpc_TicketNo" => $this->vpc_TicketNo,
			"vpc_Version" => $this->vpc_Version
		];
		$appendAmp = 0;
		foreach($requests as $key => $value) {

		    // create the md5 input and URL leaving out any fields that have no value
		    if (strlen($value) > 0) {
		        
		        // this ensures the first paramter of the URL is preceded by the '?' char
		        if ($appendAmp == 0) {
		            $vpcURL .= urlencode($key) . '=' . urlencode($value);
		            $appendAmp = 1;
		        } else {
		            $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
		        }
		        //$md5HashData .= $value; sử dụng cả tên và giá trị tham số để mã hóa
		        if ((strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
				    $md5HashData .= $key . "=" . $value . "&";
				}
		    }
		}
		//xóa ký tự & ở thừa ở cuối chuỗi dữ liệu mã hóa
		$md5HashData = rtrim($md5HashData, "&");
		// Create the secure hash and append it to the Virtual Payment Client Data if
		// the merchant secret has been provided.
		if (strlen($this->secure_secret) > 0) {
		    //$vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($md5HashData));
		    // Thay hàm mã hóa dữ liệu
		    $paymentsService->request($payment_id, serialize($requests), 0);
		    $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$this->secure_secret)));
			return $vpcURL;
		}
		return false;
	}

	public function getResult()
	{
		$paymentsService = \PaymentsService::getInstance();
		$SECURE_SECRET = $this->secure_secret;

		$vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
		$vpc_MerchTxnRef = $_GET["vpc_MerchTxnRef"];

		$order_arr = explode("-", $vpc_MerchTxnRef);
		$order_id = $order_arr[2];
		$order_type = $order_arr[0];

		if ($order_id != $this->order_id || $order_type != $this->order_type) return false;

		$vpc_AcqResponseCode = $_GET["vpc_AcqResponseCode"];
		unset($_GET["vpc_SecureHash"]);
		unset($_GET["payment_id"]);
		// set a flag to indicate if hash has been validated
		$errorExists = false;

		if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {

		    ksort($_GET);
		    //$md5HashData = $SECURE_SECRET;
		    //khởi tạo chuỗi mã hóa rỗng
		    $md5HashData = "";
		    // sort all the incoming vpc response fields and leave out any with no value
		    $payment = new \Payment();
		    $payment->data->response = serialize($_GET);
		    $payment->where = "id = {$_GET['payment_id']}";
		    $payment->update();

		    foreach ($_GET as $key => $value) {
		//        if ($key != "vpc_SecureHash" or strlen($value) > 0) {
		//            $md5HashData .= $value;
		//        }
		//      chỉ lấy các tham số bắt đầu bằng "vpc_" hoặc "user_" và khác trống và không phải chuỗi hash code trả về
		        if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
				    $md5HashData .= $key . "=" . $value . "&";
				}
		    }
		//  Xóa dấu & thừa cuối chuỗi dữ liệu
		    $md5HashData = rtrim($md5HashData, "&");

		//    if (strtoupper ( $vpc_Txn_Secure_Hash ) == strtoupper ( md5 ( $md5HashData ) )) {
		//    Thay hàm tạo chuỗi mã hóa
			if (strtoupper ( $vpc_Txn_Secure_Hash ) == strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)))) {
		        // Secure Hash validation succeeded, add a data field to be displayed
		        // later.
		        $hashValidated = "CORRECT";
		    } else {
		        // Secure Hash validation failed, add a data field to be displayed
		        // later.
		        $hashValidated = "INVALID HASH";
		    }
		} else {
		    // Secure Hash was not validated, add a data field to be displayed later.
		    $hashValidated = "INVALID HASH";
		}

		// Define Variables
		// ----------------
		// Extract the available receipt fields from the VPC Response
		// If not present then let the value be equal to 'No Value Returned'

		// Standard Receipt Data
		$amount = null2unknown($_GET["vpc_Amount"]);
		$locale = null2unknown($_GET["vpc_Locale"]);
		$batchNo = null2unknown($_GET["vpc_BatchNo"]);
		$command = null2unknown($_GET["vpc_Command"]);
		$message = null2unknown($_GET["vpc_Message"]);
		$version = null2unknown($_GET["vpc_Version"]);
		$cardType = null2unknown($_GET["vpc_Card"]);
		$orderInfo = null2unknown($_GET["vpc_OrderInfo"]);
		$receiptNo = null2unknown($_GET["vpc_ReceiptNo"]);
		$merchantID = null2unknown($_GET["vpc_Merchant"]);
		//$authorizeID = null2unknown($_GET["vpc_AuthorizeId"]);
		$merchTxnRef = null2unknown($_GET["vpc_MerchTxnRef"]);
		$transactionNo = null2unknown($_GET["vpc_TransactionNo"]);
		$acqResponseCode = null2unknown($_GET["vpc_AcqResponseCode"]);
		$txnResponseCode = null2unknown($_GET["vpc_TxnResponseCode"]);
		// 3-D Secure Data
		$verType = array_key_exists("vpc_VerType", $_GET) ? $_GET["vpc_VerType"] : "No Value Returned";
		$verStatus = array_key_exists("vpc_VerStatus", $_GET) ? $_GET["vpc_VerStatus"] : "No Value Returned";
		$token = array_key_exists("vpc_VerToken", $_GET) ? $_GET["vpc_VerToken"] : "No Value Returned";
		$verSecurLevel = array_key_exists("vpc_VerSecurityLevel", $_GET) ? $_GET["vpc_VerSecurityLevel"] : "No Value Returned";
		$enrolled = array_key_exists("vpc_3DSenrolled", $_GET) ? $_GET["vpc_3DSenrolled"] : "No Value Returned";
		$xid = array_key_exists("vpc_3DSXID", $_GET) ? $_GET["vpc_3DSXID"] : "No Value Returned";
		$acqECI = array_key_exists("vpc_3DSECI", $_GET) ? $_GET["vpc_3DSECI"] : "No Value Returned";
		$authStatus = array_key_exists("vpc_3DSstatus", $_GET) ? $_GET["vpc_3DSstatus"] : "No Value Returned";

		// *******************
		// END OF MAIN PROGRAM
		// *******************

		// FINISH TRANSACTION - Process the VPC Response Data
		// =====================================================
		// For the purposes of demonstration, we simply display the Result fields on a
		// web page.

		// Show 'Error' in title if an error condition
		$errorTxt = "";

		// Show this page as an error page if vpc_TxnResponseCode equals '7'
		if ($txnResponseCode == "7" || $txnResponseCode == "No Value Returned" || $errorExists) {
		    $errorTxt = "Error ";
		}

		// This is the display title for 'Receipt' page 
		$title = $_GET["Title"];

		// The URL link for the receipt to do another transaction.
		// Note: This is ONLY used for this example and is not required for 
		// production code. You would hard code your own URL into your application
		// to allow customers to try another transaction.
		//TK//$againLink = URLDecode($_GET["AgainLink"]);

		$transStatus = "";
		$status = 0;
		if($hashValidated=="CORRECT" && $txnResponseCode=="0"){
			$paymentsService->response($this->payment_id, serialize($_GET), 1);
			$transStatus = "Giao dịch thành công";
			$status = 1;
		}elseif ($hashValidated=="INVALID HASH" && $txnResponseCode=="0"){
			$paymentsService->response($this->payment_id, serialize($_GET), 0);
			$transStatus = "Giao dịch Pendding";
			$status = 0;
		}else {
			$paymentsService->response($this->payment_id, serialize($_GET), 2);
			$transStatus = "Giao dịch thất bại";
			$status = 2;
		}

		return  [
			'order_id' => $order_id,
			'order_type' => $order_type,
			'status' => $status
		];
	}
}