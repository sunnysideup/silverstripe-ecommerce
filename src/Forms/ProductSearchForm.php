<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Api\KeywordSearchBuilder;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Validation\ProductSearchFormValidator;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPageController;
use Sunnysideup\Vardump\Vardump;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\RelatedProductGroups;

/**
 * Product search form.
 */
class ProductSearchForm extends Form
{


    /**
     * a product group that creates the base list.
     *
     * @var ProductGroup
     */
    protected $baseListOwner;


    // /**
    //  * this is mysql specific, see: https://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html.
    //  * not used at the moment!
    //  * @var bool
    //  */
    // protected $useBooleanSearch = true;

    /**
     * get parameters added to the link
     * you dont need to start them with & or ?
     * e.g.
     * a=23&b=234.
     *
     * @var string
     */
    protected $additionalGetParameters = '';

    /**
     * @var bool
     */
    private static $include_price_filters = true;

    /**
     *
     * @param Controller $controller - associated controller
     * @param string     $name       - name of form
     */
    public function __construct($controller, string $name)
    {
        $this->extraBuyableFieldsToSearchFullText = Config::inst()->get(static::class, 'extra_buyable_fields_to_search_full_text_default');
        $request = $controller->getRequest();
        $defaults = [
            'Keyword' => $request->getVar('Keyword'),
            'MinimumPrice' => (float) $request->getVar('MinimumPrice'),
            'MaximumPrice' => (float) $request->getVar('MaximumPrice'),
            'OnlyThisSection' => ((int) $request->getVar('OnlyThisSection') - 0 ? 1 : 0),
        ];
        //fields
        $fields = FieldList::create();
        //turn of security to allow caching of the form:
        if ($this->config()->get('include_price_filters')) {
            $fields->push(
                NumericField::create('MinimumPrice', _t('ProductSearchForm.MINIMUM_PRICE', 'Minimum Price'), $defaults['MinimumPrice'])->setScale(2),
            );
            $fields->push(
                NumericField::create('MaximumPrice', _t('ProductSearchForm.MAXIMUM_PRICE', 'Maximum Price'), $defaults['MaximumPrice'])->setScale(2)
            );
        }
        $fields->push(
            $keywordField = TextField::create('Keyword', _t('ProductSearchForm.KEYWORDS', 'Keywords'), Convert::raw2att($defaults['Keyword']))
        );
        $fields->push(
            HiddenField::create('OnlyThisSection', $defaults['OnlyThisSection'])
        );
        $keywordField->setAttribute('placeholder', _t('ProductSearchForm.KEYWORD_PLACEHOLDER', 'search products ...'));

        if (Director::isDev() || Permission::check('ADMIN')) {
            $fields->push(CheckboxField::create('DebugSearch', 'Debug Search'));
        }
        // actions
        $actions = FieldList::create(
            FormAction::create('doProductSearchForm', 'Search')
        );

        // required fields
        $requiredFields = [];
        $validator = ProductSearchFormValidator::create($requiredFields);

        $this->extend('updateFields', $fields);
        $this->extend('updateActions', $actions);
        $this->extend('updateValidator', $validator);
        parent::__construct($controller, $name, $fields, $actions, $validator);
        //make it an easily accessible form  ...
        $this->setFormMethod('get');
        $this->disableSecurityToken();
        //extensions need to be set after __construct
        //extension point
        $this->extend('updateProductSearchForm', $this);

        return $this;
    }

    public function forTemplate()
    {
        if ($this->hasOnlyThisSection()) {
            $title = _t('ProductSearchForm.ONLY_SHOW', 'Only search in');
            if ($this->baseListOwner) {
                $title .= ' <em>' . $this->baseListOwner->Title . '</em> ';
            }
            $title = DBField::create_field('HTMLText', $title);
            $this->Fields()->replaceField(
                'OnlyThisSection',
                CheckboxField::create(
                    'OnlyThisSection',
                    $title,
                    1
                )
            );
        }

        return parent::forTemplate();
    }


    //#######################################
    // set-ers
    //#######################################

    public function setAdditionalGetParameters(string $s): self
    {
        $this->additionalGetParameters = $s;

        return $this;
    }

    /**
     * @param ProductGroup $baseListOwner
     */
    public function setBaseListOwner($baseListOwner): self
    {
        $this->baseListOwner = $baseListOwner;

        return $this;
    }

    /**
     * saves the form into session.
     */
    public function saveDataToSession()
    {
        $data = $this->getData();
        $data = Sanitizer::remove_from_data_array($data);
        $this->setSessionData($data);
    }

    //#######################################
    // do-ers
    //#######################################

    public function doProductSearchForm($data, $form)
    {
        $this->runFullProcessInner($data);
        $this->doProcessResults();
    }

    protected function runFullProcessInner($data)
    {
        $this->doProcessSetup($data);

    }

    /**
     * set up basics, using data.
     */
    protected function doProcessSetup(array $data)
    {
        $this->saveDataToSession();
        unset($this->rawData['action_doProductSearchForm']);
    }

    /**
     * finalise results.
     */
    protected function doProcessResults()
    {
        //you can add more details here in extensions of this form.
        $this->extend('updateProcessResults');
        $redirectToPage = $this->getResultsPage();
        $link = $redirectToPage->Link();
        $link .= '?' . http_build_query($this->rawData, '', '...');
        if ($this->additionalGetParameters) {
            $link .= '&' . trim($this->additionalGetParameters, '&');
        }
        $this->controller->redirect($link);
    }
    //#######################################
    // get-ers
    //#######################################

    protected function hasOnlyThisSection(): bool
    {
        if ($this->controller instanceof ProductGroupSearchPageController) {
            return false;
        }
        if ($this->baseListOwner instanceof ProductGroupSearchPage) {
            return false;
        }
        if ($this->baseListOwner) {
            return $this->baseListOwner->getProducts()->count() > 0;
        }

        return true;
    }

    /**
     *
     * @return ProductGroup
     */
    protected function getResultsPage()
    {
        if (empty($this->rawData['OnlyThisSection'])) {
            return ProductGroupSearchPage::main_search_page();
        }
        //if no specific section is being searched then we redirect to search page:
        return $this->controller->dataRecord;
    }



}
