<?php


class CMSPageAddController_Products extends CMSPageAddController {

	private static $url_segment = 'product/add';
	private static $url_rule = '/$Action/$ID/$OtherID';
	private static $url_priority = 41;
	private static $menu_title = 'Add Product';
	private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

	private static $allowed_actions = array(
		'AddForm',
		'doAdd',
		'doCancel'
	);
	/**
	 *
	 * @return Form
	 */
	public function AddForm(){
		return parent::AddForm();
	}

	/**
	 *
	 * @return
	 */
	public function PageTypes(){
		$pageTypes = parent::PageTypes();
		$result = new ArrayList();
		foreach($pageTypes as $type) {
			if(is_a($type->ClassName, "Product", true) || is_a($type->ClassName, "ProductGroup", true)) {
				$result->push($type);
			}
		}
		return $result;
	}

}
