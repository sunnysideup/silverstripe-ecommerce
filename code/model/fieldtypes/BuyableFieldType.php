<?php


/**
 * NOTE: this is not yet being used!!!
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class BuyableFieldType extends DBField implements CompositeDBField {

	/**
	 * @var string $BuyableClassName
	 */
	protected $BuyableClassName;

	/**
	 * @var Int $BuyableID
	 */
	protected $BuyableID;

	/**
	 * @var boolean $isChanged
	 */
	protected $isChanged = false;

	/**
	 * @param array
	 */
	static $composite_db = array(
		"BuyableID" => "Int",
		"BuyableClassName" => "Varchar(60)"
	);

	function __construct($name = null) {
		parent::__construct($name);
	}

	function compositeDatabaseFields() {
		return self::$composite_db;
	}

	function requireField() {
		$fields = $this->compositeDatabaseFields();
		if($fields);
		foreach($fields as $name => $type){
			DB::requireField($this->tableName, $name, $type);
		}
	}

	function writeToManipulation(&$manipulation) {
		if($this->getBuyableClassName()) {
			$manipulation['fields']['BuyableClassName'] = $this->prepValueForDB($this->getBuyableClassName());
		}
		else {
			$manipulation['fields']['BuyableClassName'] = DBField::create_field('Varchar', $this->getBuyableClassName())->nullValue();
		}
		if($this->getBuyableID()) {
			$manipulation['fields']['BuyableID'] = $this->getBuyableID();
		}
		else {
			$manipulation['fields']['BuyableID'] = DBField::create_field('Decimal', $this->getBuyableID())->nullValue();
		}
	}

	function setValue($value, $record = null, $markChanged = true) {
		// @todo Allow resetting value to NULL through Money $value field
		if ($value instanceof Money && $value->hasValue()) {
			$this->setBuyableClassName($value->getBuyableClassName(), $markChanged);
			$this->setBuyableID($value->getBuyableID(), $markChanged);
			if($markChanged) $this->isChanged = true;
		}
		else if($record && isset($record[$this->Name . 'BuyableClassName']) && isset($record[$this->Name . 'BuyableID'])) {
			if($record[$this->Name. 'BuyableClassName'] && $record[$this->Name . 'BuyableID']) {
				$this->setBuyableClassName($record[$this->Name . 'BuyableClassName'], $markChanged);
				$this->setBuyableID($record[$this->Name . 'BuyableID'], $markChanged);
			} else {
				$this->value = $this->nullValue();
			}
			if($markChanged) $this->isChanged = true;
		} else if (is_array($value)) {
			if (array_key_exists('BuyableClassName', $value)) {
				$this->setBuyableClassName($value['BuyableClassName'], $markChanged);
			}
			if (array_key_exists('BuyableID', $value)) {
				$this->setBuyableID($value['BuyableID'], $markChanged);
			}
			if($markChanged) $this->isChanged = true;
		} else {
			// @todo Allow to reset a money value by passing in NULL
			//user_error('Invalid value in Money->setValue()', E_USER_ERROR);
		}
	}

	/**
	 * @return string
	 */
	function Nice($options = array()) {
		$BuyableID = $this->getBuyableID();
		if(!isset($options['display'])) $options['display'] = Zend_BuyableClassName::USE_SYMBOL;
		if(!isset($options['buyableClassName'])) $options['buyableClassName'] = $this->getBuyableClassName();
		if(!isset($options['symbol'])) $options['symbol'] = $this->buyableClassNameLib->getSymbol($this->getBuyableClassName(), $this->getLocale());
		return (is_numeric($BuyableID)) ? $this->buyableClassNameLib->toBuyableClassName($BuyableID, $options) : '';
	}

	/**
	 * @return string
	 */
	function NiceWithShortname($options = array()){
		$options['display'] = Zend_BuyableClassName::USE_SHORTNAME;
		return $this->Nice($options);
	}

	/**
	 * @return string
	 */
	function NiceWithName($options = array()){
		$options['display'] = Zend_BuyableClassName::USE_NAME;
		return $this->Nice($options);
	}

	/**
	 * @return string
	 */
	function getBuyableClassName() {
		return $this->buyableClassName;
	}

	/**
	 * @param string
	 */
	function setBuyableClassName($buyableClassName, $markChanged = true) {
		$this->buyableClassName = $buyableClassName;
		if($markChanged) $this->isChanged = true;
	}

	/**
	 * @todo Return casted Float DBField?
	 *
	 * @return float
	 */
	function getBuyableID() {
		return $this->BuyableID;
	}

	/**
	 * @param float $BuyableID
	 */
	function setBuyableID($BuyableID, $markChanged = true) {
		$this->BuyableID = (float)$BuyableID;
		if($markChanged) $this->isChanged = true;
	}

	/**
	 * @return boolean
	 */
	function hasValue() {
		return ($this->getBuyableClassName() && is_numeric($this->getBuyableID()));
	}

	/**
	 * @return boolean
	 */
	function hasBuyableID() {
		return (int)$this->getBuyableID() != '0';
	}

	function isChanged() {
		return $this->isChanged;
	}

	/**
	 * @param string $locale
	 */
	function setLocale($locale) {
		$this->locale = $locale;
		$this->buyableClassNameLib->setLocale($locale);
	}

	/**
	 * @return string
	 */
	function getLocale() {
		return ($this->locale) ? $this->locale : i18n::get_locale();
	}

	/**
	 * @return string
	 */
	function getSymbol($buyableClassName = null, $locale = null) {

		if($locale === null) $locale = $this->getLocale();
		if($buyableClassName === null) $buyableClassName = $this->getBuyableClassName();

		return $this->buyableClassNameLib->getSymbol($buyableClassName, $locale);
	}

	/**
	 * @return string
	 */
	function getShortName($buyableClassName = null, $locale = null) {
		if($locale === null) $locale = $this->getLocale();
		if($buyableClassName === null) $buyableClassName = $this->getBuyableClassName();

		return $this->buyableClassNameLib->getShortName($buyableClassName, $locale);
	}

	/**
	 * @return string
	 */
	function getName($buyableClassName = null, $locale = null) {
		if($locale === null) $locale = $this->getLocale();
		if($buyableClassName === null) $buyableClassName = $this->getBuyableClassName();
		return $this->buyableClassNameLib->getName($buyableClassName, $locale);
	}

	/**
	 * @param array $arr
	 */
	function setAllowedCurrencies($arr) {
		$this->allowedCurrencies = $arr;
	}

	/**
	 * @return array
	 */
	function getAllowedCurrencies() {
		return $this->allowedCurrencies;
	}

	/**
	 * Returns a CompositeField instance used as a default
	 * for form scaffolding.
	 *
	 * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
	 *
	 * @param string $title Optional. Localized title of the generated instance
	 * @return FormField
	 */
	public function scaffoldFormField($title = null) {
		$field = new BuyableFieldType($this->Name);
		return $field;
	}

	/**
	 * For backwards compatibility reasons
	 * (mainly with ecommerce module),
	 * this returns the BuyableID value of the field,
	 * rather than a {@link Nice()} formatting.
	 */
	function __toString() {
		return (string)$this->getBuyableID();
	}
}
