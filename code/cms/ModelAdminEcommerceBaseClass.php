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
			if(!class_exists($model)) {
				unset($models[$key]);
			}
		}
		self::$managed_models = $models;
		return parent::getManagedModels();
	}

	/**
	 * Change this variable if you don't want the Import from CSV form to appear.
	 * This variable can be a boolean or an array.
	 * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo')
	 */
	public $showImportForm = false;

}
/**
 * @Descrition: Common functionality for ModelAdmin
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy

class ModelAdminBaseClass_CollectionController extends ModelAdmin_CollectionController {


	function search($request, $form) {
		// Get the results form to be rendered
		$query = $this->getSearchQuery(array_merge($form->getData(), $request));
		$resultMap = new SS_MAP($query, $keyField = "ID", $titleField = "Title");
		$items = $resultMap->getItems();
		$array = array();
		if($items && $items->count()) {
			foreach($items as $item) {
				$array[] = $item->ID;
			}
		}
		Session::set("ModelAdminEcommerceClass".$this->modelClass,serialize($array));
		return parent::search($request, $form);
	}

	function urlSegmenter() {
		return self::$url_segment;
	}
}
class ModelAdminEcommerceClass_RecordController extends ModelAdmin_RecordController {


	protected static $actions_to_keep = array(
		"Back",
		"doDelete",
		"doSave"
	);


	/**
	 * Returns a form for editing the attached model
	 *
	 *@return Form
	public function EditForm() {
		$form = parent::EditForm();
		if($this->currentRecord instanceof SiteTree){
			$oldActions = $form->Actions();
			//in order of appearance
			//$form->unsetActionByName("action_doDelete"); - USEFUL TO KEEP
			$form->unsetActionByName("action_unpublish");
			$form->unsetActionByName("action_delete");
			$form->unsetActionByName("action_save");
			$form->unsetActionByName("action_publish");
			//$form->unsetActionByName("action_doSave"); - USEFUL TO KEEP
			$actions = $form->Actions();
			//$actions->push(new FormAction("doGoto", "go to page"));
			$form->setActions($actions);
		}
		if($this->parentController) {
			$array = unserialize(Session::get("ModelAdminEcommerceClass".$this->parentController->getModelClass()));
			if(is_array($array)) {
				if(count($array) && count($array) > 1) {
					foreach($array as $key => $id) {
						if($id == $this->currentRecord->ID) {
							if(isset($array[$key + 1]) && $array[$key + 1]) {
								$nextRecordID = $array[$key + 1];
								$nextRecordURL = $this->parentController->Link(Controller::join_links($nextRecordID, "edit"));
								$form->Actions()->push(new FormAction("goNext", "Next Record"));
								$form->Fields()->push(new HiddenField("nextRecordURL", null, $nextRecordURL));
							}
							if(isset($array[$key - 1]) && $array[$key - 1]) {
								$prevRecordID = $array[$key - 1];
								$nextRecordURL = $this->parentController->Link(Controller::join_links($prevRecordID, "edit"));
								$form->Actions()->insertFirst(new FormAction("goPrev", "Previous Record"));
								$form->Fields()->push(new HiddenField("prevRecordURL", null, $nextRecordURL));
							}
						}
					}
				}
			}
		}
		return $form;
	}


	/**
	 * Postback action to save a record
	 *
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 * @return mixed
	function doSave($data, $form, $request) {
		$form->saveInto($this->currentRecord);
		if($this->currentRecord instanceof SiteTree){
			try {
				$this->currentRecord->writeToStage("Stage");
				$this->currentRecord->publish("Stage", "Live");
			}
			catch(ValidationException $e) {
				$form->sessionMessage($e->getResult()->message(), 'bad');
			}
		}
		else {
			try {
				$this->currentRecord->write();
			}
			catch(ValidationException $e) {
				$form->sessionMessage($e->getResult()->message(), 'bad');
			}
		}

		// Behaviour switched on ajax.
		if(Director::is_ajax()) {
			return $this->edit($request);
		}
		else {
			$this->redirectBack();
		}
	}

	//function doGoto($data, $form, $request) {
		//$this->redirect($this->currentRecord->Link());
	//}


}
*/
