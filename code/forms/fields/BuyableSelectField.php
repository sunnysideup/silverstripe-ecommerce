<?php
/**
 * Text input field.
 * @package forms
 * @subpackage fields
 * @inspiration: https://github.com/sheadawson/silverstripe-zenautocompletefield
 */
class BuyableSelectField extends FormField {

	protected $suggestions;

	/**
	 * number of suggestions
	 * @var Int
	 */
	protected $countOfSuggestions;

	/**
	 * @var FormField
	 */
	protected $fieldFindBuyable = null;

	/**
	 * @var FormField
	 */
	protected $fieldSelectedBuyable = null;

	/**
	 * @var DataObject
	 */
	protected $buyable = null;

	/**
	 * @param String $name
	 * @param String $title
	 * @param Object $buyable - currently selected buyable
	 * @param Int $countOfSuggestions - number of suggestions shown (max)
	 * @param Form $form
	 */
	function __construct($name, $title = null, $buyable = null, $countOfSuggestions = 12, $form = null) {
		$this->countOfSuggestions = $countOfSuggestions;
		$this->fieldFindBuyable = new TextField("{$name}[FindBuyable]", _t('BuyableSelectField.FIELDLABELFINDBUYABLE', 'Find Product'));
		$this->fieldSelectedBuyable = new ReadonlyField("{$name}[SelectedBuyable]", _t('BuyableSelectField.FIELDLABELSELECTEDBUYABLE', ''), _t('BuyableSelectField.NONE', 'None'));
		$this->buyable = $buyable;
		if($this->buyable) {
			$value = $this->buyable->FullName ? $this->buyable->FullName : $this->buyable->getTitle();
		}
		else {
			$value = "";
		}
		parent::__construct($name, $title, $value, $form);
	}

	function hasData() { return false; }

	/**
	 * @return string
	 */
	function Field() {
		if(!$this->form->dataFieldByName("Version")) {
			user_error("You must have a Version field in your form");
		}
		if(!$this->form->dataFieldByName("BuyableClassName")) {
			user_error("You must have a BuyableClassName field in your form");
		}
		if(!$this->form->dataFieldByName("BuyableID")) {
			user_error("You must have a BuyableID field in your form");
		}
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.core.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.widget.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.position.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.autocomplete.js');
		Requirements::customScript($this->getJavascript(), "BuyableSelectField".$this->id());
		Requirements::themedCSS("BuyableSelectField");
		return
		"<div class=\"fieldgroup\">" .
			"<div class=\"selectedBuyable fieldGroupInner\">" . $this->fieldSelectedBuyable->SmallFieldHolder()."</div>".
			"<div class=\"findBuyable fieldGroupInner\">" . $this->fieldFindBuyable->SmallFieldHolder()."</div>" .
		"</div>";
	}

	protected function getJavascript(){
		return '(function($){
			jQuery(function() {

				var requestTerm = "";

				jQuery( "#'.$this->name().'-FindBuyable").autocomplete({
					 source: function(request, response) {
						jQuery("body").css("cursor", "progress");
						requestTerm = request.term;
						jQuery.ajax({
							type: "POST",
							url: "/ecommercebuyabledatalist/json/",
							dataType: "json",
							data: {
								term: request.term,
								countOfSuggestions: '.$this->countOfSuggestions.'
							},
							error: function(xhr, textStatus, errorThrown) {
								alert(\'Error: \' + xhr.responseText+errorThrown+textStatus);
							},
							success: function(data) {
								response(
									jQuery.map(
										data,
										function(c) {
											return {
												label: c.Title,
												value: requestTerm,
												title: c.Title,
												className: c.ClassName,
												id: c.ID,
												version: c.Version
											}
										}
									)
								);
							}
						});
					},
					minLength: 2,
					select: function(event, ui) {
						if(
							jQuery("input[name=\'BuyableID\']").length == 0 ||
							jQuery("input[name=\'BuyableClassName\']").length  == 0 ||
							jQuery("input[name=\'Version\']").length  == 0
						) {
							alert("Error: can not find BuyableID or BuyableClassName or Version field");
						}
						else {
							jQuery("input[name=\'BuyableID\']").val(ui.item.id);
							jQuery("input[name=\'BuyableClassName\']").val(ui.item.className);
							jQuery("input[name=\'Version\']").val(ui.item.version);
							jQuery("input[name=\''.$this->fieldSelectedBuyable->name().'\']").val(ui.item.title);
							jQuery("span#'.$this->fieldSelectedBuyable->id().'").text(ui.item.title);
						}
						jQuery("body").css("cursor", "auto");
					}
				});
			});
		})(jQuery);';
	}

	function setValue($data) {
		if($this->buyable) {
			$value = $this->buyable->FullName ? $this->buyable->FullName : $this->buyable->getTitle();
			$this->fieldSelectedBuyable->setValue($value);
		}
	}

	/**
	 * Returns a readonly version of this field.
	 */
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}

	/**
	 */
	function setReadonly($bool) {
		parent::setReadonly($bool);
		if($bool) {
			$this->fieldFindBuyable = $this->fieldFindBuyable->performReadonlyTransformation();
			$this->fieldSelectedBuyable = $this->fieldSelectedBuyable->performReadonlyTransformation();
		}
	}

}

class BuyableSelectField_DataList extends Controller {

	protected $fieldsToSearch = array(
		"InternalItemID",
		"Title",
		"FullName",
		"MetaKeywords"
	);

	function json($request){
		$countOfSuggestions = $request->requestVar("countOfSuggestions");
		$term = Convert::raw2sql($request->requestVar("term"));
		$arrayOfBuyables = EcommerceConfig::get("EcommerceDBConfig", "array_of_buyables");
		$arrayOfAddedItemIDsByClassName = array();
		$array = array();
		//search by InternalID ....
		foreach($this->fieldsToSearch as $fieldName) {
			foreach($arrayOfBuyables as $buyableClassName) {
				if(!isset($arrayOfAddedItemIDsByClassName[$buyableClassName])) {
					$arrayOfAddedItemIDsByClassName[$buyableClassName] = array(-1);
				}
				$obj = DataObject::get_one(
					$buyableClassName,
					"\"$fieldName\" LIKE '%$term%'
						AND \"$buyableClassName\".\"ID\" NOT IN (".implode(",", $arrayOfAddedItemIDsByClassName[$buyableClassName]).")
						AND \"AllowPurchase\" = 1
					"
				);
				if($obj) {
					$arrayOfAddedItemIDsByClassName[$buyableClassName][] = $obj->ID;
					if($obj->canPurchase()) {
						$array[$buyableClassName.$obj->ID] = array(
							"ClassName" => $buyableClassName,
							"ID" => $obj->ID,
							"Version" => $obj->Version,
							"Title" => $obj->FullName ? $obj->FullName : $obj->getTitle(),
						);
					}
				}
				if(count($array) >= $countOfSuggestions) {
					$this->fieldsToSearch = array();
					$arrayOfBuyables = array();
				}
			}
		}
		//remove KEYS
		$finalArray = array();
		foreach($array as $item) {
			$finalArray[] = $item;
		}
		return $this->array2json($finalArray);
	}


	/**
	 * converts an Array into JSON and formats it nicely for easy debugging
	 * @param Array $array
	 * @return JSON
	 */
	protected function array2json($array){
		$json = Convert::array2json($array);
		return $json;
	}


}
