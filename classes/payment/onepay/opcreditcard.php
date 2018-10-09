<?php
namespace Amely\Payment\OnePay;

class OPCreditCard extends \Object implements \Amely\Payment\IPaymentMethod
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


	public $vpc_MerchTxnRef;
	public $vpc_OrderInfo;
	public $vpc_Amount;
	public $vpc_ReturnURL;
	
	public $vpc_SHIP_Street01;
	public $vpc_SHIP_Provice;
	public $vpc_SHIP_City;
	public $vpc_SHIP_Country;
	public $vpc_Customer_Phone;
	public $vpc_Customer_Email;
	public $vpc_Customer_Id;
	public $AVS_Street01;
	public $AVS_City;
	public $AVS_StateProv;
	public $AVS_PostCode;
	public $AVS_Country;
	public $display;

	function __construct()
	{
		$this->Title = "";
		$this->virtualPaymentClientURL = "https://mtf.onepay.vn/vpcpay/vpcpay.op";
		$this->vpc_Merchant = "TESTONEPAY";
		$this->vpc_AccessCode = "6BEB2546";
		$this->secure_secret = '6D0870CDE5F24F34F3915FB0045120DB';
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
		$return_url = $settings['url'].$settings['prefix'].'/payment_response';
		$creator = $this->creator;

		$this->vpc_MerchTxnRef = $this->po_id;
		$this->vpc_OrderInfo = $this->description;
		$this->vpc_Amount = $this->amount*100;
		$this->AVS_Street01 = $creator->address;
		$this->AVS_City = $creator->province;
		$this->AVS_StateProv = $creator->district;
		$this->AVS_Country = "VN";

		$vpcURL = $this->virtualPaymentClientURL . "?";
		unset($this->virtualPaymentClientURL); 

		$md5HashData = "";
		$time = time();
		$requests = [
			"AVS_City" => "Hanoi",
			"AVS_Country" => "VN",
			"AVS_PostCode" => 10000,
			"AVS_StateProv" => "Hoan Kiem",
			"AVS_Street01" => "194 Tran Quang Khai",
			"AgainLink" => urlencode($return_url),
			"Title" => "Thanh Toan ONEPAY",
			"display" => "mobile",
			"vpc_AccessCode" => $this->vpc_AccessCode,
			"vpc_Amount" => 1000000,
			"vpc_Command" => $this->vpc_Command,
			"vpc_Customer_Email" => "support@onepay.vn",
			"vpc_Customer_Id" => "thanhvt",
			"vpc_Customer_Phone" => "840904280949",
			"vpc_Locale" => $this->vpc_Locale,
			"vpc_MerchTxnRef" => $time,
			"vpc_Merchant" => $this->vpc_Merchant,
			"vpc_OrderInfo" => $time,
			"vpc_ReturnURL" => $return_url,
			"vpc_SHIP_City" => "Ha Noi",
			"vpc_SHIP_Country" => "Viet Nam",
			"vpc_SHIP_Provice" => "Hoan Kiem",
			"vpc_SHIP_Street01" => "39A Ngo Quyen",
			"vpc_TicketNo" => "127.0.0.1",
			"vpc_Version" => 2
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
		    $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$this->secure_secret)));
		}
		return $vpcURL;
	}

	public function getResult()
	{
		$SECURE_SECRET = $this->secure_secret;

		// get and remove the vpc_TxnResponseCode code from the response fields as we
		// do not want to include this field in the hash calculation
		$vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
		$vpc_MerchTxnRef = $_GET["vpc_MerchTxnRef"];
		$vpc_AcqResponseCode = $_GET["vpc_AcqResponseCode"];
		unset($_GET["vpc_SecureHash"]);
		// set a flag to indicate if hash has been validated
		$errorExists = false;

		if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {

		    ksort($_GET);
		    //$md5HashData = $SECURE_SECRET;
		    //khởi tạo chuỗi mã hóa rỗng
		    $md5HashData = "";
		    // sort all the incoming vpc response fields and leave out any with no value
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
		if($hashValidated=="CORRECT" && $txnResponseCode=="0"){
			$transStatus = "Giao dịch thành công";
		}elseif ($hashValidated=="INVALID HASH" && $txnResponseCode=="0"){
			$transStatus = "Giao dịch Pendding";
		}else {
			$transStatus = "Giao dịch thất bại";
		}

		var_dump($transStatus);
		die();
	}

	
}