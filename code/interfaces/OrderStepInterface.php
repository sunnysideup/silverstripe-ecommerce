<?php
/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: buyables
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

interface OrderStepInterface {

	/**
	 * Initiate the step. REturns true if the step is ready to run.
	 * You should be able to run this method many times without causing problems.
	 * @param Order - $order
	 * @return Boolean
	 **/
	public function initStep(Order $order);

	/**
	 * Do the actual step.
	 * Returns true if the step runs successfully.
	 * You should be able to run this method many times without causing problems.
	 * @param Order - $order
	 * @return Boolean
	 **/
	public function doStep(Order $order);

	/**
	 * Returns the nextStep when we are ready or null if we are not ready.
	 * You should be able to run this method many times without causing problems.
	 * @param Order
	 * @return OrderStep | Null (nextStep DataObject)
	 **/
	public function nextStep(Order $order);

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 * You should be able to run this method many times without causing problems.
	 * @param FieldList $fields
	 * @param Order $order
	 * @return FieldList
	 **/
	function addOrderStepFields(FieldList $fields, Order $order);

}
