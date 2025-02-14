<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\GetVariables;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Forms\Validation\ProductSearchFormValidator;
use Sunnysideup\Ecommerce\Model\Search\ProductGroupSearchTable;
use Sunnysideup\Ecommerce\Model\Search\ProductSearchTable;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPageController;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSearchFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\ProductGroupSchema;

/**
 * Product search form.
 */
class ProductSearchForm extends Form
{
    /**
     * @var array
     */
    protected $rawData = [];
    /**
     * @var array
     */
    protected $cleanedData = [];

    /**
     * a product group that creates the base list.
     *
     * @var ProductGroup
     */
    protected $baseListOwner;

    /**
     * get parameters added to the link
     * you dont need to start them with & or ?
     * e.g.
     * a=23&b=234.
     *
     * @var string
     */
    protected $additionalGetParameters = '';

    private static $default_sort = ProductSearchFilter::KEY_FOR_SORTER;

    /**
     * @var bool
     */
    private static $include_price_filters = true;

    /**
     * @param Controller $controller - associated controller
     * @param string     $name       - name of form
     */
    public function __construct($controller, string $name)
    {
        $request = $controller->getRequest();
        $getVars = $request->requestVars();
        $searchVar = $this->getVariableContainingSearchParams();
        $condensedData = $request->getVar($searchVar);
        unset($getVars[$searchVar]);
        if ($condensedData) {
            $data = GetVariables::url_string_to_array($condensedData);
            $getVars = array_merge($getVars, $data);
        }
        $defaults = [];
        $defaults['Keyword'] = $getVars['Keyword'] ?? '';
        $defaults['MinimumPrice'] = $getVars['MinimumPrice'] ?? 0;
        $defaults['MaximumPrice'] = $getVars['MaximumPrice'] ?? 0;
        $defaults['OnlyThisSection'] = $getVars['OnlyThisSection'] ?? 0;
        $defaults = [
            'Keyword' => Convert::raw2att($defaults['Keyword']),
            'MinimumPrice' => (float) str_replace(', ', '', (string) $defaults['MinimumPrice']),
            'MaximumPrice' => (float) str_replace(', ', '', (string) $defaults['MaximumPrice']),
            'OnlyThisSection' => (int) $defaults['OnlyThisSection'] ? 1 : 0,
        ];
        $this->rawData = $defaults;

        //fields
        $fields = FieldList::create();
        //turn of security to allow caching of the form:
        $fields->push(
            $keywordField = TextField::create('Keyword', _t('ProductSearchForm.KEYWORDS', 'Keywords'), $defaults['Keyword'])
                ->setAttribute('autocomplete', 'off')
                ->setAttribute('autocorrect', 'off')
                ->setAttribute('spellcheck', 'false')
        );
        $keywordField->setAttribute('placeholder', _t('ProductSearchForm.KEYWORD_PLACEHOLDER', 'search products ...'));
        if ($this->config()->get('include_price_filters')) {
            $fields->push(
                CompositeField::create(
                    LiteralField::create('PriceHeader', '<label class="left">' . _t('ProductSearchForm.PRICE_RANGE', 'Price Range') . '</label>'),
                    CompositeField::create(
                        NumericField::create('MinimumPrice', '$', $defaults['MinimumPrice'] ?: '')->setScale(2)->setAttribute('placeholder', 'Min'),
                        LiteralField::create('PriceSeparator', '<label class="separator">' . _t('ProductSearchForm.TO', 'To') . '</label>'),
                        NumericField::create('MaximumPrice', '$', $defaults['MaximumPrice'] ?: '')->setScale(2)->setAttribute('placeholder', 'Max')
                    )
                        ->setName('PriceRangeInner')
                        ->addExtraClass('min-max-inner')
                )
                    ->setName('PriceRange')
                    ->addExtraClass('min-max-holder')
            );
        }
        $fields->push(
            HiddenField::create('OnlyThisSection', $defaults['OnlyThisSection'])
        );

        if (Director::isDev() || Permission::check('ADMIN')) {
            $fields->push(CheckboxField::create('showdebug', 'Debug Search'));
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
        $this->rawData = $data;
        $this->rawData['Keyword'] = $data['Keyword'] ?? '';
        $this->rawData['Keyword'] = ProductSearchFilter::keyword_sanitised($this->rawData['Keyword']);
        SearchHistory::add_entry($this->rawData['Keyword']);
        $this->runFullProcessInner($data);
        $this->doProcessResults();
    }

    protected function runFullProcessInner($data)
    {
        $this->saveDataToSession();
        foreach ($this->Fields()->dataFields() as $field) {
            $name = $field->getName();
            if (!empty($this->rawData[$name])) {
                $this->cleanedData[$name] = $this->rawData[$name];
            }
        }
    }

    /**
     * finalise results.
     */
    protected function doProcessResults()
    {
        //you can add more details here in extensions of this form.
        $this->extend('updateProcessResults');
        $doSearchAtAll = false;
        if (!empty($this->rawData['OnlyThisSection'])) {
            $doSearchAtAll = true;
        } elseif (!$this->checkForInternalItemID()) {
            if (!$this->checkForOneProductTitleMatch()) {
                if (!$this->checkForOneCategoryTitleMatch()) {
                    $doSearchAtAll = true;
                }
            }
        }
        if ($doSearchAtAll) {
            $link = $this->getResultsPageLink();
            if (!strpos('?', $link)) {
                $link .= '?';
            } else {
                $link .= '&';
            }
            $link .= $this->getVariableContainingSearchParams() . '=' . GetVariables::array_to_url_string($this->cleanedData);
            if ($this->additionalGetParameters) {
                $link .= '&' . trim((string) $this->additionalGetParameters, '&');
            }
            //important - sort by relevancy
            $link .= '&' . $this->getVariableContainingSortParam() . '=' . $this->defaultSort();
            $this->controller->redirect($link);
        }
    }

    //#######################################
    // get-ers
    //#######################################

    protected function getVariableContainingSearchParams(): string
    {
        return Injector::inst()->get(ProductGroupSchema::class)->getSortFilterDisplayValues('SEARCHFILTER', 'getVariable');
    }

    protected function getVariableContainingSortParam(): string
    {
        return Injector::inst()->get(ProductGroupSchema::class)->getSortFilterDisplayValues('SORT', 'getVariable');
    }

    protected function hasOnlyThisSection(): bool
    {
        if ($this->controller instanceof ProductGroupSearchPageController) {
            return false;
        }
        if ($this->baseListOwner instanceof ProductGroupSearchPage) {
            return false;
        }
        if ($this->baseListOwner) {
            return $this->baseListOwner->getProducts()->exists();
        }

        return true;
    }

    /**
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

    protected function defaultSort(): string
    {
        return $this->Config()->get('default_sort');
    }

    protected function getResultsPageLink(): string
    {
        $redirectToPage = $this->getResultsPage();

        return $redirectToPage->Link();
    }

    protected function getProductClassName(): string
    {
        return Product::class;
    }

    protected function getProductGroupClassName(): string
    {
        return ProductGroup::class;
    }

    protected function checkForInternalItemID()
    {
        return $this->checkForOneInner($this->getProductClassName(), ['InternalItemID' => $this->rawData['Keyword']]);
    }

    protected function checkForOneProductTitleMatch()
    {
        return $this->checkForOneInner($this->getProductClassName(), ['Title' => $this->rawData['Keyword']]);
    }

    protected function checkForOneCategoryTitleMatch()
    {
        return $this->checkForOneInner($this->getProductGroupClassName(), ['Title' => $this->rawData['Keyword']]);
    }

    protected function checkForOneInner(string $className, array $filterArray)
    {
        if ($this->rawData['Keyword']) {
            $filterArray['ShowInSearch'] = 1;
            $productList = $className::get()->filter($filterArray);
            if ($productList->count() === 1) {
                $obj = $productList->first();
                return $this->controller->redirect($obj->Link());
            }
        }

        return false;
    }
}
