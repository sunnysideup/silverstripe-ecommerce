<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;

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
     * @var SilverStripe\ORM\DataObject|null
     */
    protected $buyable = null;

    /**
     * @param string $name
     * @param string $title
     * @param object $buyable            - currently selected buyable
     * @param int    $countOfSuggestions - number of suggestions shown (max)
     */
    public function __construct($name, $title = null, $buyable = null, $countOfSuggestions = 100)
    {
        $this->countOfSuggestions = $countOfSuggestions;
        $this->fieldFindBuyable = new TextField("{$name}[FindBuyable]", _t('BuyableSelectField.FIELDLABELFINDBUYABLE', 'Enter product code or title'));
        $this->fieldSelectedBuyable = new ReadonlyField("{$name}[SelectedBuyable]", _t('BuyableSelectField.FIELDLABELSELECTEDBUYABLE', ' '), _t('BuyableSelectField.NONE', 'No product selected yet.'));
        $this->buyable = $buyable;
        if ($this->buyable) {
            $value = $this->buyable->FullName ?: $this->buyable->getTitle();
        } else {
            $value = '';
        }
        parent::__construct($name, $title, $value);
    }

    public function hasData()
    {
        return false;
    }

    /**
     * @return DBHTMLText
     */
    public function Field($properties = [])
    {
        //Requirements::javascript($this->jquery_UI_JS_location);
        //Requirements::css($this->jquery_UI_CSS_location);
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomBuyableSelectField.js');
        Requirements::customScript($this->getJavascript(), BuyableSelectField::class . $this->id());
        Requirements::themedCSS('BuyableSelectField');

        $field =
        '<div class="fieldgroup">' .
            '<div class="findBuyable fieldGroupInner">' . $this->fieldFindBuyable->SmallFieldHolder() . '</div>' .
            '<div class="selectedBuyable fieldGroupInner">' . $this->fieldSelectedBuyable->SmallFieldHolder() . '</div>' .
        '</div>';

        return DBField::create_field('HTMLText', $field);
    }

    /**
     * Do we do anything with data???
     */
    public function setValue($value, $data = null)
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
