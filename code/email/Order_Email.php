<?php


/**
 * @Description: Email specifically for communicating with customer about order.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: email
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

Abstract class Order_Email extends Email {

	/**
	 * @var Order
	 */
	protected $order = null;


	/**
	 * @var Boolean
	 */
	protected $resend = false;

	/**
	 * turns an html document into a formatted html document
	 * using the emogrify method.
	 * @param $html
	 * @return String HTML
	 */
	public static function emogrify_html($html){
		//get required files
		$baseFolder = Director::baseFolder() ;
		if(!class_exists('Emogrifier')) {
			require_once($baseFolder . '/ecommerce/thirdparty/Emogrifier.php');
		}
		$cssFileLocation = Director::baseFolder()."/".EcommerceConfig::get("Order_Email", "css_file_location");
		$cssFileHandler = fopen($cssFileLocation, 'r');
		$css = fread($cssFileHandler,  filesize($cssFileLocation));
		fclose($cssFileHandler);
		$emogrifier = new Emogrifier($html, $css);
		$html = $emogrifier->emogrify();
		//make links absolute!
		$html = HTTP::absoluteURLs($html);
		return $html;
	}

	/**
	 * returns the standard from email address (e.g. the shop admin email address)
	 * @return String
	 */
	public static function get_from_email() {
		$ecommerceConfig = EcommerceDBConfig::current_ecommerce_db_config();
		if($ecommerceConfig && $ecommerceConfig->ReceiptEmail) {
			return $ecommerceConfig->ReceiptEmail;
		}
		else {
			return Email::getAdminEmail();
		}
	}

	/**
	 * returns the subject for the email (doh!).
	 * @return String
	 */
	static function get_subject() {
		$siteConfig = SiteConfig::current_site_config();
		if($siteConfig && $siteConfig->Title) {
			return _t("Order_Email.SALEUPDATE", "Sale Update [OrderNumber] from ").$siteConfig->Title;
		}
		else {
			return _t("Order_Email.SALEUPDATE", "Sale Update [OrderNumber] ");
		}
	}

	/**
	 * set the order associated with the email
	 * @param Order $order - the order to which the email relates
	 *
	 */
	public function setOrder(Order $order) {
		$this->order = $order;
	}

	/**
	 * sets resend to true, which means that the email
	 * is sent even if it has already been sent.
	 */
	public function setResend($resend = true) {
		$this->resend = $resend;
	}

	/**
	 *
	 * @param Null|String $messageID - ID for the message, you can leave this blank
	 * @param Boolean $returnBodyOnly - rather than sending the email, only return the HTML BODY
	 * @return Boolean - TRUE for success and FALSE for failure.
	 */
	public function send($messageID = null, $returnBodyOnly = false) {
		if(!$this->order) {
			user_error("Must set the order (Order_Email::setOrder()) before the message is sent (Order_Email::send()).", E_USER_NOTICE);
		}
		if(!$this->subject) {
			$this->subject = self::get_subject();
		}
		$this->subject = str_replace("[OrderNumber]", $this->order->ID, $this->subject);
		if((!$this->hasBeenSent()) || ($this->resend)) {
			if(EcommerceConfig::get("Order_Email", "copy_to_admin_for_all_emails") && ($this->to != Email::getAdminEmail())) {
				$this->setBcc(Email::getAdminEmail());
			}
			//last chance to adjust
			$this->extend("adjustOrderEmailSending", $this, $order);
			if($returnBodyOnly) {
				return $this->Body();
			}
			if(EcommerceConfig::get("Order_Email", "send_all_emails_plain")) {
				$result = parent::sendPlain($messageID);
			}
			else {
				$result = parent::send($messageID);
			}
			$this->createRecord($result);
			return $result;
		}
	}

	/**
	 * @param Boolean $result: how did the email go? 1 = sent, 0 = not sent
	 * @return DataObject (OrderEmailRecord)
	 **/
	protected function createRecord($result) {
		$obj = new OrderEmailRecord();
		$obj->From = $this->emailToVarchar($this->from);
		$obj->To = $this->emailToVarchar($this->to);
		if($this->cc) {
			$obj->To .= ", CC: ".$this->emailToVarchar($this->cc);
		}
		if($this->bcc) {
			$obj->To .= ", BCC: ".$this->emailToVarchar($this->bcc);
		}
		$obj->Subject = $this->subject;
		$obj->Content = $this->body;
		$obj->Result = $result ? 1 : 0;
		$obj->OrderID = $this->order->ID;
		$obj->OrderStepID = $this->order->StatusID;
		if(Email::$send_all_emails_to) {
			$obj->To = Email::$send_all_emails_to." - (Email::send_all_emails_to setting)";
		}
		$obj->write();
		return $obj;
	}

	/**
	 * converts an Email to A Varchar
	 * @param String $email - email address
	 * @return String - returns email address without &gt; and &lt;
	 */
	function emailToVarchar($email) {
		$email = str_replace(array("<", ">", '"', "'"), " - ", $email);
		return $email;
	}

	/**
	 * Checks if an email has been sent for this Order for this status (order step)
	 * @return boolean
	 **/
	function hasBeenSent() {
		$orderStep = $this->order->Status();
		if($orderStep instanceOf OrderStep)  {
			return $orderStep->hasBeenSent($this->order);
		}
		return false;
	}

	/**
	 * moves CSS to inline CSS in email
	 * @param Boolean $isPlain - should we send the email as HTML or as TEXT
	 */
	protected function parseVariables($isPlain = false) {
		//start parsing
		parent::parseVariables($isPlain);
		if(!$isPlain) {
			$this->body = self::emogrify_html($this->body);
		}
	}

	/**
	 * returns the instance of EcommerceDBConfig
	 *
	 * @return EcommerceDBConfig
	 **/
	public function EcomConfig(){
		return EcommerceDBConfig::current_ecommerce_db_config();
	}

}
