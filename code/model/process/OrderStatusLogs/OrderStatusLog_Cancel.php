<?php



/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_Cancel extends OrderStatusLog {

	private static $defaults = array(
		"Title" => "Order Cancelled",
		"InternalUseOnly" => false
	);

	private static $singular_name = "Cancelled Order";
		function i18n_singular_name() { return _t("OrderStatusLog.SUBMITTEDORDER", "Cancelled Order");}

	private static $plural_name = "Cancelled Orders";
		function i18n_plural_name() { return _t("OrderStatusLog.SUBMITTEDORDERS", "Cancelled Orders");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A record noting the cancellation of an order.  ";

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return false;
	}


}
