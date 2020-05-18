<?php
/**
 * Text input field that allows the user to select a Buyable.
 * A product, a product variation or any other buyable.
 * using the auto-complete technique from jQuery UI.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: https://github.com/sheadawson/silverstripe-zenautocompletefield
 **/
class BuyableSelectField extends FormField
{
    /**
     * Location for jQuery UI library location.
     *
     * @var string
     */
    protected $jquery_UI_JS_location = null; //'ecommerce/thirdparty/jquery-ui/jquery-ui-1.8.23.custom.min.js';

    /**
     * Location for jQuery UI library location.
     *
     * @var string
     */
    protected $jquery_UI_CSS_location = null; //'ecommerce/thirdparty/jquery-ui/jquery-ui-1.8.23.custom.css';

    /**
     * number of suggestions.
     *
     * @var int
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
     * @param string $name
     * @param string $title
     * @param object $buyable            - currently selected buyable
     * @param int    $countOfSuggestions - number of suggestions shown (max)
     * @param Form   $form
     */
    public function __construct($name, $title = null, $buyable = null, $countOfSuggestions = 100, $form = null)
    {
        $this->countOfSuggestions = $countOfSuggestions;
        $this->fieldFindBuyable = new TextField("{$name}[FindBuyable]", _t('BuyableSelectField.FIELDLABELFINDBUYABLE', 'Enter product code or title'));
        $this->fieldSelectedBuyable = new ReadonlyField("{$name}[SelectedBuyable]", _t('BuyableSelectField.FIELDLABELSELECTEDBUYABLE', ''), _t('BuyableSelectField.NONE', 'No product selected yet.'));
        $this->buyable = $buyable;
        if ($this->buyable) {
            $value = $this->buyable->FullName ?: $this->buyable->getTitle();
        } else {
            $value = '';
        }
        parent::__construct($name, $title, $value, $form);
    }

    public function hasData()
    {
        return false;
    }

    /**
     * @return string
     */
    public function Field($properties = [])
    {
        //Requirements::javascript($this->jquery_UI_JS_location);
        //Requirements::css($this->jquery_UI_CSS_location);
        Requirements::javascript('ecommerce/javascript/EcomBuyableSelectField.js');
        Requirements::customScript($this->getJavascript(), 'BuyableSelectField' . $this->id());
        Requirements::themedCSS('BuyableSelectField', 'ecommerce');

        return '<div class="fieldgroup">' .
            '<div class="findBuyable fieldGroupInner">' . $this->fieldFindBuyable->SmallFieldHolder() . '</div>' .
            '<div class="selectedBuyable fieldGroupInner">' . $this->fieldSelectedBuyable->SmallFieldHolder() . '</div>' .
        '</div>';
    }

    /**
     * Do we do anything with data???
     */
    public function setValue($data)
    {
        if ($this->buyable) {
            // $value = $this->buyable->FullName ?: $this->buyable->getTitle();
            //to TEST!!!
            $this->fieldSelectedBuyable->setValue('Once you have selected a new value, it will appear here...');
        }
    }

    /**
     * Returns a readonly version of this field.
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);

        return $clone;
    }

    public function setReadonly($bool)
    {
        parent::setReadonly($bool);
        if ($bool) {
            $this->fieldFindBuyable = $this->fieldFindBuyable->performReadonlyTransformation();
            $this->fieldSelectedBuyable = $this->fieldSelectedBuyable->performReadonlyTransformation();
        }
    }

    /**
     * set alternative location for jQuerry UI Autocomplete JAVASCRIPT FILE.
     *
     * @see http://jqueryui.com/download
     *
     * @param string $pathFileName
     */
    public function set_jquery_UI_JS_location($pathFileName)
    {
        $this->jquery_UI_JS_location = $pathFileName;
    }

    /**
     * set alternative location for jQuerry UI Autocomplete CSS File.
     *
     * @see http://jqueryui.com/download
     *
     * @param string $pathFileName
     */
    public function set_jquery_UI_CSS_location($pathFileName)
    {
        $this->jquery_UI_CSS_location = $pathFileName;
    }

    protected function getJavascript()
    {
        return '
        EcomBuyableSelectField.set_nothingFound("' . _t('BuyableSelectField.NOTHINGFOUND', 'no products found - please try again') . '");
        EcomBuyableSelectField.set_fieldName("' . Convert::raw2js($this->getName()) . '");
        EcomBuyableSelectField.set_formName("' . Convert::raw2js($this->form->FormName()) . '");
        EcomBuyableSelectField.set_countOfSuggestions(' . $this->countOfSuggestions . ');
        EcomBuyableSelectField.set_selectedBuyableFieldName("' . Convert::raw2js($this->fieldSelectedBuyable->getName()) . '");
        EcomBuyableSelectField.set_selectedBuyableFieldID("' . Convert::raw2js($this->fieldSelectedBuyable->id()) . '");
        ';
    }
}

class BuyableSelectField_DataList extends Controller
{
    protected $fieldsToSearch = [
        'InternalItemID',
        'Title',
        'FullName',
        'MetaDescription',
    ];

    private static $allowed_actions = [
        'json',
    ];

    private static $url_segment = 'ecommercebuyabledatalist';

    public function Link($action = null)
    {
        $URLSegment = Config::inst()->get($this->class, 'url_segment');
        if (! $URLSegment) {
            $URLSegment = $this->class;
        }

        return Controller::join_links(
            Director::BaseURL(),
            $URLSegment,
            $action
        );
    }

    /**
     * returns JSON in this format:
     * Array(
     *  ClassName => $className,
     *  ID => $obj->ID,
     *  Version => $obj->Version,
     *  Title => $name
     * );.
     *
     * @param SS_HTTPRequest $request
     *
     * @return string (JSON)
     */
    public function json(SS_HTTPRequest $request)
    {
        $countOfSuggestions = $request->requestVar('countOfSuggestions');
        $term = Convert::raw2sql($request->requestVar('term'));
        $arrayOfBuyables = EcommerceConfig::get('EcommerceDBConfig', 'array_of_buyables');
        $arrayOfAddedItemIDsByClassName = [];
        $lengthOfFieldsToSearch = count($this->fieldsToSearch);
        $lenghtOfBuyables = count($arrayOfBuyables);
        $array = [];
        //search by InternalID ....
        $absoluteCount = 0;
        $buyables = [];
        foreach ($arrayOfBuyables as $key => $buyableClassName) {
            $buyables[$key] = [];
            $singleton = singleton($buyableClassName);
            $buyables[$key]['Singleton'] = $singleton;
            $buyables[$key]['ClassName'] = $buyableClassName;
            $buyables[$key]['TableName'] = $buyableClassName;
            if (is_a($singleton, Object::getCustomClass('SiteTree'))) {
                if (Versioned::current_stage() === 'Live') {
                    $buyables[$key]['TableName'] .= '_Live';
                }
            }
        }
        unset($arrayOfBuyables);
        while ((count($array) <= $countOfSuggestions) && ($absoluteCount < 30)) {
            ++$absoluteCount;
            for ($i = 0; $i < $lengthOfFieldsToSearch; ++$i) {
                $fieldName = $this->fieldsToSearch[$i];
                for ($j = 0; $j < $lenghtOfBuyables; ++$j) {
                    $buyableArray = $buyables[$j];
                    $singleton = $buyableArray['Singleton'];
                    $className = $buyableArray['ClassName'];
                    $tableName = $buyableArray['TableName'];
                    if (! isset($arrayOfAddedItemIDsByClassName[$className])) {
                        $arrayOfAddedItemIDsByClassName[$className] = [-1 => -1];
                    }
                    if ($singleton->hasDatabaseField($fieldName)) {
                        $where = "\"${fieldName}\" LIKE '%${term}%'
                                AND \"" . $tableName . '"."ID" NOT IN
                                AND "AllowPurchase" = 1';
                        $obj = $className::get()
                            ->filter([
                                $fieldName . ':PartialMatch' => $term,
                                'AllowPurchase' => 1,
                            ])
                            ->where("\"${tableName}\".\"ID\" NOT IN (" . implode(',', $arrayOfAddedItemIDsByClassName[$className]) . ')')
                            ->First();
                        if ($obj) {
                            //we found an object, we dont need to find it again.
                            $arrayOfAddedItemIDsByClassName[$className][$obj->ID] = $obj->ID;
                            //now we are only going to add it, if it is available!
                            if ($obj->canPurchase()) {
                                $useVariationsInstead = false;
                                if ($obj->hasExtension('ProductWithVariationDecorator')) {
                                    $variations = $obj->Variations();
                                    if ($variations->count()) {
                                        $useVariationsInstead = true;
                                    }
                                }
                                if (! $useVariationsInstead) {
                                    $name = $obj->FullName ?: $obj->getTitle();
                                    $array[$className . $obj->ID] = [
                                        'ClassName' => $className,
                                        'ID' => $obj->ID,
                                        'Version' => $obj->Version,
                                        'Title' => $name,
                                    ];
                                }
                            }
                        }
                    }
                    //echo $singleton->ClassName ." does not have $fieldName";
                }
            }
        }
        //remove KEYS
        $finalArray = [];
        $count = 0;
        foreach ($array as $item) {
            if ($count < $countOfSuggestions) {
                $finalArray[] = $item;
            }
            ++$count;
        }

        return $this->array2json($finalArray);
    }

    /**
     * converts an Array into JSON and formats it nicely for easy debugging.
     *
     * @param array $array
     *
     * @return JSON
     */
    protected function array2json(array $array)
    {
        return Convert::array2json($array);
    }
}
