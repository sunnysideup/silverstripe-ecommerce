<?php

/**
 *
 * @see: http://doc.silverstripe.org/framework/en/reference/ModelAdmin
 * @author Nicolaas [at] sunnyside up . co .nz
 */

class ModelAdminEcommerceBaseClass extends ModelAdmin {


	/**
	 * @return array Map of class name to an array of 'title' (see {@link $managed_models})
	 */
	function getManagedModels() {
		$models = EcommerceConfig::get($this->class, "managed_models");
		foreach($models as $key => $model) {
			if(is_array($model)) {
				$model = $key;
			}
			if(!class_exists($model)) {
				unset($models[$key]);
			}
		}
		Config::inst()->update('ModelAdminEcommerceBaseClass', 'managed_models', $models);
		return parent::getManagedModels();
	}

	/**
	 * Change this variable if you don't want the Import from CSV form to appear.
	 * This variable can be a boolean or an array.
	 * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo')
	 */
	public $showImportForm = false;

}

