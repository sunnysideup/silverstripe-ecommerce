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

		// populate Javascript
		$js = array ();

		//order items
		if ($items = $currentOrder->Items()) {
			foreach ($items as $item) {
				$item->updateForAjax($js);
				//products in cart
				$js[] = array(
					"id" => $item->Buyable()->UniqueIdentifier(),
					"parameter" => "class",
					"value" => "inCart"
				);
			}
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
			foreach($messages as $messageArray) {
				$messagesImploded .= '<span class="'.$messageArray["Type"].'">'.$messageArray["Message"].'</span>';
			}
			$js[] = array(
				"id" => $currentOrder->TableMessageID(),
				"parameter" => "innerHTML",
				"value" => $messagesImploded,
				"isOrderMessage" => true
			);
			$js[] = array(
				"id" =>  $currentOrder->TableMessageID(),
				"parameter" => "hide",
				"value" => 0
			);
		}
		else {
			$js[] = array(
				"id" => $currentOrder->TableMessageID(),
				"parameter" => "hide",
				"value" => 1
			);
		}

		//tiny cart
		$js[] = array(
			"class" => $currentOrder->MenuCartClass(),
			"parameter" => "innerHTML",
			"value" => $currentOrder->renderWith("CartTinyInner")
		);

		//add basic cart
		$js[] = array(
			"id" => $currentOrder->SideBarCartID(),
			"parameter" => "innerHTML",
			"value" => $currentOrder->renderWith("CartShortInner")
		);

		//merge and return
		if(is_array($data)) {
			$js = array_merge($js, $data);
		}
		return str_replace("{", "\r\n{", Convert::array2json($js));
	}

}
