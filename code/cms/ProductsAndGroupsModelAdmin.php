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

	private static $menu_priority = 3.2;

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
	private static $menu_icon = "ecommerce/images/icons/product-file.gif";


	function init() {
		parent::init();
		//Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
	}

	function getEditForm($id = null, $fields = null){
		$form = parent::getEditForm();
		if(singleton($this->modelClass) instanceof SiteTree) {
			if($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
				if($gridField instanceof GridField) {
					$gridField->getConfig()
						->removeComponentsByType("GridFieldEditButton")
						->removeComponentsByType("GridFieldDeleteAction")
						->addComponent(new GridFieldEditButtonOriginalPage());
				}
			}
		}
		elseif($this->modelClass == "SearchHistory") {
			if($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
				$form->Fields()->replaceField($gridField->getName(), EcommerceSearchHistoryFormField::create("SearchHistoryTable"));
			}
		}
		return $form;
	}

}
