<?php

/**
 * @description: returns the cart as JSON
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class CartResponse extends EcommerceResponse {

	/**
	 * Should the page be reloaded rather than using AJAX?
	 * @var Boolean $force_reload
	 */
	protected static $force_reload = false;

	/**
	 * Sets the $force_reload to true;
	 */
	public static function set_force_reload() {
		self::$force_reload = true;
	}

	/**
	 * Builds json object to be returned via ajax.
	 *
	 *@return JSON
	 **/
	public function ReturnCartData($messages = array(), $data = null, $status = "success") {
		//add header
		$this->addHeader('Content-Type', 'application/json');

		SSViewer::set_source_file_comments(false);

		//merge messages
		$messagesImploded = '';
		if(is_array($messages) && count($messages)) {
			foreach($messages as $messageArray) {
				$messagesImploded .= '<span class="'.$messageArray["Type"].'">'.$messageArray["Message"].'</span>';
			}
		}

		//bad status
		if($status != "success") {
			$this->setStatusCode(400, $messagesImploded);
		}

		//init Order - IMPORTANT
		$currentOrder = ShoppingCart::current_order();

		//THIS LINE TAKES UP MOST OF THE TIME OF THE RESPONSE!!!
		$currentOrder->calculateOrderAttributes($force = false);

		$ajaxObject = $currentOrder->AJAXDefinitions();
		// populate Javascript
		$js = array ();

		//must be first
		if(isset($_REQUEST["loadingindex"])) {
			$js[] = array(
				"t" => "loadingindex",
				"v" => $_REQUEST["loadingindex"]
			);
		}

		//order items

		$inCartArray = array();

		if ($items = $currentOrder->Items()) {
			foreach ($items as $item) {
				$item->updateForAjax($js);
				$buyable = $item->Buyable(true);
				if($buyable) {
					//products in cart
					$inCartArray[] = $buyable->AJAXDefinitions()->UniqueIdentifier();
					//HACK TO INCLUDE PRODUCT IN PRODUCT VARIATION
					if($buyable instanceOf ProductVariation){
						$inCartArray[] = $buyable->Product()->AJAXDefinitions()->UniqueIdentifier();
					}
				}
			}
		}

		//in cart items
		$js[] = array(
			"t" => "replaceclass",
			"s" => $inCartArray,
			"p" => ".productActions.inCart",
			"v" => "inCart",
			"without" => "notInCart"
		);

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
			$js[] = array(
				"t" => "id",
				"s" => $ajaxObject->TableMessageID(),
				"p" => "innerHTML",
				"v" => $messagesImploded,
				"isOrderMessage" => true
			);
			$js[] = array(
				"t" =>  "id",
				"s" =>  $ajaxObject->TableMessageID(),
				"p" => "hide",
				"v" => 0
			);
		}
		else {
			$js[] = array(
				"t" => "id",
				"s" => $ajaxObject->TableMessageID(),
				"p" => "hide",
				"v" => 1
			);
		}

		//TO DO: set it up in such a way that it specifically requests one of these
		//tiny cart
		$js[] = array(
			"t" => "class",
			"s" => $ajaxObject->TinyCartClassName(),
			"p" => "innerHTML",
			"v" => $currentOrder->renderWith("CartTinyInner")
		);

		//add basic cart
		$js[] = array(
			"t" => "id",
			"s" => $ajaxObject->SmallCartID(),
			"p" => "innerHTML",
			"v" => $currentOrder->renderWith("CartShortInner")
		);

		//side bar cart
		$js[] = array(
			"t" => "id",
			"s" => $ajaxObject->SideBarCartID(),
			"p" => "innerHTML",
			"v" => $currentOrder->renderWith("Sidebar_Cart_Inner")
		);
		//now can check if it needs to be reloaded
		if(self::$force_reload) {
			$js = array(
				"reload" => 1
			);
		}
		else {
			$js[] = array(
				"reload" => 0
			);
		}

		//merge and return
		if(is_array($data)) {
			$js = array_merge($js, $data);
		}
		//TODO: remove doubles?
		$json = json_encode($js);
		$json = str_replace('\t', " ", $json);
		$json = str_replace('\r', " ", $json);
		$json = str_replace('\n', " ", $json);
		$json = preg_replace('/\s\s+/', ' ', $json);
		if(Director::isDev()) {
			$json = str_replace("{", "\r\n{", $json);
		}
		return $json;
	}


}
