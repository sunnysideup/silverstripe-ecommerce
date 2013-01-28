<?php

/**
 * this is a dropdown field just for selecting the right
 * classname
 *
 *
 */
class EcommerceClassNameOrTypeDropdownField extends DropdownField{

	/**
	 *
	 * @var Array
	 */
	protected $availableClasses = array();

	/**
	 * @var String
	 */
	protected $sourceClass = "";

	/**
	 * @var Boolean
	 */
	protected $includeBaseClass = false;

	/**
	 *
	 * @param String $name - this is usually classname, as in MyTable.ClassName
	 * @param String $title - e.g. type of object
	 * @param String $sourceClass - e.g. MyDataObject
	 * @param Array $availableClasses - e.g. Array(MyFavouriteClassName, MyOtherFavouriteClassName)

	 *
	 * @return EcommerceClassNameOrTypeDropdownField
	 */
	public function __construct($name = "ClassName", $title = "Type", $sourceClass, array $availableClasses=array(), $value='', $form=null){
		$this->sourceClass = $sourceClass;
		$this->availableClasses = $availableClasses;
		parent::__construct($name, $title, array(), $value, $form);
	}

	function getSource(){
		if($this->includeBaseClass) {
			$classes[] = $this->sourceClass;
		}
		else {
			$classes = array();
		}
		$classes = $classes + ClassInfo::subclassesFor($this->sourceClass);

		if(!count($this->availableClasses)) {
			$this->availableClasses = $classes;
		}
		elseif($this->includeBaseClass) {
			$this->availableClasses[] = $this->sourceClass;
		}
		$dropdownArray = array();
		if($this->getHasEmptyDefault()) {
			$dropdownArray[""] = $this->emptyString ;
		}
		if($classes) {
			foreach($classes as $key => $className) {
				if(class_exists($key)) {
					if(in_array($className, $this->availableClasses )) {
						$obj = singleton($className);
						if($obj) {
							$dropdownArray[$className] = $obj->i18n_singular_name();
						}
					}
				}
			}
		}
		if(!count($dropdownArray)) {
			$dropdownArray= array($this->sourceClass => _t("EcommerceClassNameOrTypeDropdownField.CAN_NOT_CREATE", "Can't create.").$title);
		}
		return $dropdownArray;
	}

	/**
	 *
	 * @param Array $availableClasses - e.g. Array(MyFavouriteClassName, MyOtherFavouriteClassName)
	 */
	function setAvailableClasses(Array $array){
		$this->availableClasses = $array;
	}

	/**
	 *
	 * @param Boolean $bool
	 */
	function setIncludeBaseClass($bool){
		$this->includeBaseClass = $bool;
	}

	/*
	function Field($properties = array()) {
		$this->source = $this->getSource();
		return parent::Field($properties);
	}
	*/

}
