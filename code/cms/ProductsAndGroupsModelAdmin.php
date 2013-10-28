<?php


/**
 * @description: Manages everything you sell.
 * Can include ProductVariations, etc..
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/

class ProductsAndGroupsModelAdmin extends ModelAdminEcommerceBaseClass {

	private static $menu_priority = 2;

	private static $url_segment = 'products';

	private static $menu_title = 'Products';

	private static $allowed_actions = array(
		"editinsitetree",
		"ItemEditForm"
	);

	/**
	 * standard SS variable
	 * @var String
	 */
	private static $menu_icon = "";


	function init() {
		parent::init();
		//Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
	}

	/**
	 *@return String (URL Segment)
	 **/
	function urlSegmenter() {
		return self::$url_segment;
	}

	function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
		if(singleton($this->modelClass) instanceOf SiteTree) {
			$listfield = $form->Fields()->fieldByName($this->modelClass);
			if($gridField = $listfield->getConfig()->getComponentByType('GridFieldDetailForm')) {
				$gridField->setItemRequestClass('ProductsAndGroupsModelAdmin_FieldDetailForm_ItemRequest');
			}
		}
		return $form;
	}

}

class ProductsAndGroupsModelAdmin_FieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {

	private static $allowed_actions = array(
		"editinsitetree",
		"ItemEditForm"
	);

	function ItemEditForm() {
		$form = parent::ItemEditForm();
		$formActions = $form->Actions();
		if($actions = $this->record->getCMSActions()) {
			foreach($actions as $action) {
				$formActions->push($action);
			}
		}
		$form->setActions($formActions);
		return $form;
	}

	function editinsitetree($data, $form) {
		$controller = Controller::curr();
		$controller->response->addHeader('X-Reload', true);
		$controller->response->addHeader('X-ControllerURL', $this->Link());
		$CMSEditLink = $this->record->CMSEditLink();
		return $controller->redirect($CMSEditLink, 302);
	}


}
