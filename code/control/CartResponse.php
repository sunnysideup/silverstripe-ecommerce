<?php

/**
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: control
 * @Description: this class
 *
 **/

class CartResponse extends EcommerceResponse {


	/**
	 * Builds json object to be returned via ajax.
	 *
	 *@return JSON
	 **/
	public function ReturnCartData($messages = array(), $data = null, $status = "success") {
		//add header
		$this->addHeader('Content-Type', 'application/json');
		if($status != "success") {
			$this->setStatusCode(400, "not successful: ".$status." --- ".$messages[0]);
		}

		//init Order - IMPORTANT
		$currentOrder = ShoppingCart::current_order();
		$currentOrder->calculateOrderAttributes(true);
		$ajaxObject = $currentOrder->AJAXDefinitions();

		// populate Javascript
		$js = array ();

		//order items
		$inCartArray = array();
		if ($items = $currentOrder->Items()) {
			foreach ($items as $item) {
				$item->updateForAjax($js);
				//products in cart
				$inCartArray[] = $item->Buyable()->AJAXDefinitions()->UniqueIdentifier();
			}
		}

		//in cart items
		$js[] = array(
			"type" => "replaceclass",
			"selector" => $inCartArray,
			"parameter" => ".productActions.inCart",
			"value" => "inCart",
			"without" => "notInCart"
		);
		//in cart items
		if(isset($_REQUEST["loadingindex"])) {
			$js[] = array(
				"type" => "loadingindex",
				"value" => $_REQUEST["loadingindex"]
			);
		}
		//order modifiers
		if ($modifiers = $currentOrder->Modifiers()) {
			foreach ($modifiers as $modifier) {
				$modifier->updateForAjax($js);
			}
		}

		//order
		$currentOrder->updateForAjax($js);

		//messages
		if(is_array($messages)) {
			$messagesImploded = '';
			$messageclasses = "";
			foreach($messages as $messageArray) {
				$messagesImploded .= '<span class="'.$messageArray["Type"].'">'.$messageArray["Message"].'</span>';
			}
			$js[] = array(
				"type" => "id",
				"selector" => $ajaxObject->TableMessageID(),
				"parameter" => "innerHTML",
				"value" => $messagesImploded,
				"isOrderMessage" => true
			);
			$js[] = array(
				"type" =>  "id",
				"selector" =>  $ajaxObject->TableMessageID(),
				"parameter" => "hide",
				"value" => 0
			);
		}
		else {
			$js[] = array(
				"type" => "id",
				"selector" => $ajaxObject->TableMessageID(),
				"parameter" => "hide",
				"value" => 1
			);
		}

		//tiny cart
		$js[] = array(
			"type" => "class",
			"selector" => $ajaxObject->TinyCartClassName(),
			"parameter" => "innerHTML",
			"value" => $currentOrder->renderWith("CartTinyInner")
		);

		//add basic cart
		$js[] = array(
			"type" => "id",
			"selector" => $ajaxObject->SmallCartID(),
			"parameter" => "innerHTML",
			"value" => $currentOrder->renderWith("CartShortInner")
		);

		//merge and return
		if(is_array($data)) {
			$js = array_merge($js, $data);
		}
		//TODO: remove doubles!
		return str_replace("{", "\r\n{", Convert::array2json($js));
	}


}
