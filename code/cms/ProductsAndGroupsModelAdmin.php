<?php


/**
 * @description: Manages everything you sell.
 * Products and Product Groups are included by default - can also include ProductVariations, etc..
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/

class ProductsAndGroupsModelAdmin extends ModelAdminEcommerceBaseClass {

	public static $menu_priority = 2;

	public static $url_segment = 'products';

	public static $menu_title = 'Products';


	/**
	 * standard SS variable
	 * @var String
	 */
	public static $menu_icon = "";


	function init() {
		parent::init();
		Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
	}

	/**
	 *@return String (URL Segment)
	 **/
	function urlSegmenter() {
		return self::$url_segment;
	}


	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
		$actions = $form->Actions();
		$actions->push(new FormAction("Next", "Next"));
		$form->setActions($actions);
		return $form;
	}

	public function updateEditForm($form) {

		if ( ! singleton($this->owner->modelClass)->hasExtension('Versioned') ) return;

		$gridField = $form->Fields()->fieldByName($this->owner->modelClass);
		$gridField->getConfig()->getComponentByType('GridFieldDetailForm')->setItemEditFormCallback(function ($form) {
				$form->Actions()->push(FormAction::create('doPublish', 'Save & Publish'));
		});
	}

}

//remove side forms
/*
class ProductsAndGroupsModelAdmin_CollectionController extends ModelAdminEcommerceClass_CollectionController {

	//public function CreateForm() {return false;}
	//public function ImportForm() {return false;}

	 //note that these are called once for each $managed_models

	/**
	 *
	 *@return Form
	function ImportForm(){
		$form = parent::ImportForm();
		if($form){
			//EmptyBeforeImport checkbox does not appear to work for SiteTree objects, so removed for now
			$form->Fields()->removeByName('EmptyBeforeImport');
		}
		return $form;
	}

	/*
	//see issue 145
	function ResultsForm($searchCriteria){
		$form = parent::ResultsForm($searchCriteria);
		if($tf = $form->Fields()->fieldByName($this->modelClass)){
			$tf->actions['create'] = array(
				'label' => 'delete',
				'icon' => null,
				'icon_disabled' => 'cms/images/test.gif',
				'class' => 'testlink'
			);

			$tf->setPermissions(array(
				'create'
			));
		}
		return $form;
	}


}

class ProductsAndGroupsModelAdmin_RecordController extends ModelAdminEcommerceClass_RecordController{


}
*/
