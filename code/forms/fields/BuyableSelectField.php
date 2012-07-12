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

	function __construct($name, $title = null, $value = "", $countOfSuggestions = 12, $form = null) {
		$this->countOfSuggestions = $countOfSuggestions;
		$this->fieldFindBuyable = new TextField("{$name}[FindBuyable]", _t('BuyableSelectField.FIELDLABELFINDBUYABLE', 'Find Product'));
		parent::__construct($name, $title, $value, $form);
	}

	/**
	 * @param array
	 */
	function setSuggestions(Array $suggestions) {
		$this->suggestions = $suggestions;
	}

	/**
	 * @return string
	 */
	function Field() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.core.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.widget.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.position.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.autocomplete.js');
		Requirements::customScript($this->getJavascript(), "BuyableSelectField".$this->id());
		return
		"<div class=\"fieldgroup\">" .
			"<div class=\"fieldgroupField\">" . $this->fieldFindBuyable->SmallFieldHolder()."</div>" .
		"</div>";
	}



	protected function getJavascript(){
		return '(function($){
			jQuery(function() {
				jQuery( "#'.$this->name().'-FindBuyable").autocomplete({
					 source: function(request, response) {
						jQuery.ajax({
							type: "POST",
							url: "/ecommercebuyabledatalist/",
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
												value: c.Title,
												title: c.Title,
												className: c.ClassName,
												id: c.ID
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
							jQuery("input[name=\'BuyableClassName\']").length  == 0
						) {
							alert("Error, can not find BuyableID and BuyableClassName field");
						}
						else {
							jQuery("input[name=\'BuyableID\']").val(ui.item.id);
							jQuery("input[name=\'BuyableClassName\']").val(ui.item.className);
						}
					}
				});
			});
		})(jQuery);';
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

	function index($request){
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
					"\"$fieldName\" LIKE '%$term%' AND \"$buyableClassName\".\"ID\" NOT IN (".implode(",", $arrayOfAddedItemIDsByClassName[$buyableClassName]).")"
				);
				if($obj) {
					$arrayOfAddedItemIDsByClassName[$buyableClassName][] = $obj->ID;
					$array[$buyableClassName.$obj->ID] = array(
						"ClassName" => $buyableClassName,
						"ID" => $obj->ID,
						"Title" => $obj->FullName ? $obj->FullName : $obj->getTitle(),
					);
				}
				if(count($array) == $countOfSuggestions) {
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
