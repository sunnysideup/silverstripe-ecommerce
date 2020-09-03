<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Validation\ProductSearchFormValidator;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Model\Search\SearchReplacement;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;

/**
 * Product search form
 */
class ProductSearchForm extends Form
{

    /**
     * @var string
     */
    private static $form_data_session_variable = 'ProductGroupSearchResultsFormData';

    /**
     * @var bool
     */
    private static $include_price_filters = false;

    /**
     * @var string
     */
    private static $product_session_variable = 'ProductGroupSearchResultsProducts';

    /**
     * @var string
     */
    private static $product_group_session_variable = 'ProductGroupSearchResultsProductGroups';

    /**
     * set to TRUE to show the search logic.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * list of additional fields to add to search.
     *
     * Additional fields array is formatted as follows:
     * array(
     *  "FormField" => Field,
     *  "DBField" => Acts On / Searches,
     *  "FilterUsed" => SearchFilter
     * );
     * e.g.
     * array(
     *  [1] => array(
     *    "FormField" => TextField::create("MyDatabaseField", "Keyword"),
     *    "DBField" => "MyDatabaseField",
     *    "FilterUsed" => "PartialMatchFilter"
     *   )
     * );
     *
     * @var array
     */
    protected $additionalFields = [];

    /**
     * list of products that need to be searched.
     *
     * @var ProductList
     */
    protected $productsToSearch = null;

    /**
     * @var int
     */
    protected $productsToSearchCount = 0;

    /**
     * class name of the buyables to search
     * at this stage, you can only search one type of buyable at any one time
     * e.g. only products or only mydataobject.
     *
     * @var string
     */
    protected $baseClassNameForBuyables = '';

    /**
     * this is mysql specific, see: https://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html.
     *
     * @var bool
     */
    protected $useBooleanSearch = true;

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
     * List of additional fields that should be searched full text.
     * We are matching this against the buyable class name.
     *
     * @var array
     */
    protected $extraBuyableFieldsToSearchFullText = [
        'Product' => ['Title', 'MenuTitle', 'Content', 'MetaDescription'],
        'ProductVariation' => ['FullTitle', 'Description'],
    ];

    /**
     * The method on the parent controller that can display the results of the
     * search results.
     *
     * @var string
     */
    protected $controllerSearchResultDisplayMethod = 'searchresults';

    /**
     * array of IDs of the results found so far.
     *
     * @var array
     */
    protected $resultArray = [];

    /**
     * array of IDs of the results found so far.
     *
     * @var array
     */
    protected $resultArrayPerIternalItemID = [];

    /**
     * product groups found.
     *
     * @var array
     */
    protected $productGroupIDs = [];

    /**
     * Number of results found so far.
     *
     * @var int
     */
    protected $resultArrayPos = 0;

    /**
     * Is the extended or the short form?
     *
     * @var bool
     */
    protected $isShortForm = 0;

    /**
     * List of words to be replaced.
     *
     * @var array
     */
    protected $sqlWords = [
        "\r\n SELECT" => 'SELECT',
        "\r\n FROM" => 'FROM',
        "\r\n WHERE" => 'WHERE',
        "\r\n HAVING" => 'HAVING',
        "\r\n GROUP" => 'GROUP',
        "\r\n ORDER BY" => 'ORDER BY',
        "\r\n INNER JOIN" => 'INNER JOIN',
        "\r\n LEFT JOIN" => 'LEFT JOIN',
    ];

    /**
     * ProductsToSearch can be left blank to search all products.
     *
     * @param Controller              $controller                  - associated controller
     * @param string                  $name                        - name of form
     * @param string                  $nameOfProductsBeingSearched - name of the products being search (also see productsToSearch below)
     * @param DataList | Array | Null $productsToSearch            (see comments above)
     */
    public function __construct($controller, $name, $nameOfProductsBeingSearched = '', $productsToSearch = null)
    {
        $this->disableSecurityToken();

        if ($productsToSearch) {
            $this->productsToSearch = $productsToSearch;
            $this->productsToSearchCount = $productsToSearch->getRawCount();
        }

        if ($this->isShortForm) {
            $fields = FieldList::create(
                $shortKeywordField = TextField::create('ShortKeyword', '')
            );
            $actions = FieldList::create(
                FormAction::create('doProductSearchForm', 'Go')
            );
            $shortKeywordField->setAttribute('placeholder', _t('ProductSearchForm.SHORT_KEYWORD_PLACEHOLDER', 'search products ...'));
        } else {
            if (Config::inst()->get(ProductSearchForm::class, 'include_price_filters')) {
                $fields = FieldList::create(
                    $keywordField = TextField::create('Keyword', _t('ProductSearchForm.KEYWORDS', 'Keywords')),
                    NumericField::create('MinimumPrice', _t('ProductSearchForm.MINIMUM_PRICE', 'Minimum Price'))->setScale(2),
                    NumericField::create('MaximumPrice', _t('ProductSearchForm.MAXIMUM_PRICE', 'Maximum Price'))->setScale(2)
                );
            } else {
                $fields = FieldList::create(
                    $keywordField = TextField::create('Keyword', _t('ProductSearchForm.KEYWORDS', 'Keywords'))
                );
            }
            $actions = FieldList::create(
                FormAction::create('doProductSearchForm', 'Search')
            );

            if ($this->productsToSearcht) {
                $fields->push(
                    CheckboxField::create('SearchOnlyFieldsInThisSection', _t('ProductSearchForm.ONLY_SHOW', 'Only search in') . ' <i>' . $nameOfProductsBeingSearched . '</i> ', true)
                );
            }

            if (Director::isDev() || Permission::check('ADMIN')) {
                $fields->push(CheckboxField::create('DebugSearch', 'Debug Search'));
            }

            $keywordField->setAttribute('placeholder', _t('ProductSearchForm.KEYWORD_PLACEHOLDER', 'search products ...'));
        }

        $requiredFields = [];
        $validator = ProductSearchFormValidator::create($requiredFields);

        parent::__construct($controller, $name, $fields, $actions, $validator);

        $this->setFormMethod('get');
        $this->disableSecurityToken();
        $this->extend('updateFields', $fields);
        $this->setFields($fields);
        $this->extend('updateActions', $actions);
        $this->setActions($actions);
        $this->extend('updateValidator', $validator);
        $this->setValidator($validator);

        $oldData = Controller::curr()->getRequest()->getSession()->get($this->Config()->get('form_data_session_variable'));

        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }

        $this->extend('updateProductSearchForm', $this);

        return $this;
    }

    /**
     * @return string
     */
    public function getLastSearchPhase(): string
    {
        $string = '';
        $oldData = $this->getSessionData();

        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            if (isset($oldData['ShortKeyword'])) {
                $string = $oldData['ShortKeyword'];
            } elseif (isset($oldData['Keyword'])) {
                $string = $oldData['Keyword'];
            }
        }
        return trim($string);
    }

    /**
     * @param string $phrase
     */
    public function setLastSearchTerm($phrase)
    {
        $oldData = $this->getOldData();

        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $oldData['ShortKeyword'] = $phrase;
            $oldData['Keyword'] = $phrase;
        }

        Controller::curr()->getRequest()->getSession()->set(Config::inst()->get(ProductSearchForm::class, 'form_data_session_variable'), $phrase);
    }

    public function setControllerSearchResultDisplayMethod($s)
    {
        $this->controllerSearchResultDisplayMethod = $s;

        return $this;
    }

    public function setExtraBuyableFieldsToSearchFullText($a)
    {
        $this->extraBuyableFieldsToSearchFullText = $a;

        return $this;
    }

    public function setBaseClassNameForBuyables($s)
    {
        $this->baseClassNameForBuyables = $s;

        return $this;
    }

    public function setUseBooleanSearch($b)
    {
        $this->useBooleanSearch = $b;

        return $this;
    }

    public function setAdditionalGetParameters($s)
    {
        $this->additionalGetParameters = $s;

        return $this;
    }

    public function addAdditionalField($formField, $dbField, $filterUsed)
    {
        $this->additionalFields[$dbField] = [
            'FormField' => $formField,
            'DBField' => $dbField,
            'FilterUsed' => $filterUsed,
        ];
        $this->fields->push($formField);
    }

    public function doProductSearchForm($data, $form)
    {
        $searchHistoryObject = null;
        $immediateRedirectLink = '';
        $baseClassName = $this->baseClassForBuyables;
        $controller = $this->controller;

        if (! $baseClassName) {
            $baseClassName = EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
        }

        if (! $baseClassName) {
            user_error("Can not find ${baseClassName} (baseClassName)");
        }

        $singleton = Injector::inst()->get($baseClassName);
        $baseList = $baseClassName::get()->filter(['ShowInSearch' => 1]);
        $ecomConfig = EcommerceDBConfig::current_ecommerce_db_config();

        if ($ecomConfig->OnlyShowProductsThatCanBePurchased) {
            $baseList->filter(['AllowPurchase' => 1]);
        }

        $limitToCurrentSection = false;

        if (isset($data['SearchOnlyFieldsInThisSection']) && $data['SearchOnlyFieldsInThisSection']) {
            $limitToCurrentSection = true;
            if (! $this->productsToSearch) {

                if ($controller) {
                    $this->productsToSearch = $controller->Products();
                }
            }
            if ($this->productsToSearch instanceof DataList) {
                $this->productsToSearch = $this->productsToSearch->map('ID', 'ID')->toArray();
            }
            //last resort
            if ($this->productsToSearch) {
                $baseList = $baseList->filter(['ID' => $this->productsToSearch]);
            }
        }
        if (isset($data['MinimumPrice']) && $data['MinimumPrice']) {
            $baseList = $baseList->filter(['Price:GreaterThanOrEqual' => floatval($data['MinimumPrice'])]);
        }
        if (isset($data['MaximumPrice']) && $data['MaximumPrice']) {
            $baseList = $baseList->filter(['Price:LessThanOrEqual' => floatval($data['MaximumPrice'])]);
        }
        //defining some variables
        $isKeywordSearch = false;

        if (isset($data['ShortKeyword']) && ! isset($data['Keyword'])) {
            $data['Keyword'] = $data['ShortKeyword'];
        }

        if (isset($data['Keyword']) && $keywordPhrase = $data['Keyword']) {
            if ($baseList->count()) {
                if (strlen($keywordPhrase) > 1) {
                    $isKeywordSearch = true;
                    $immediateRedirectLink = '';
                    $this->resultArrayPos = 0;
                    $this->resultArray = [];
                    if ($this->debug) {
                        $this->debugOutput('<hr /><h3>Raw Keyword ' . $keywordPhrase . '</h3><pre>');
                    }
                    $keywordPhrase = Convert::raw2sql($keywordPhrase);
                    $keywordPhrase = strtolower($keywordPhrase);

                    $searchHistoryObjectID = SearchHistory::add_entry($keywordPhrase);
                    if ($searchHistoryObjectID) {
                        $searchHistoryObject = SearchHistory::get()->byID($searchHistoryObjectID);
                    }

                    // 1) Exact search by code
                    $count = 0;
                    if ($this->debug) {
                        $this->debugOutput('<hr /><h2>SEARCH BY CODE</h2>');
                    }
                    $list1 = $baseList->filter(['InternalItemID' => $keywordPhrase]);
                    $count = $list1->count();
                    if ($count === 1) {
                        $immediateRedirectLink = $list1->First()->getRequestHandler()->Link();
                        $this->controller->redirect($immediateRedirectLink);
                        $this->debugOutput('<p style="color: red">Found one answer for potential immediate redirect: ' . $immediateRedirectLink . '</p>');
                    }
                    if ($count > 0) {
                        if ($this->addToResults($list1)) {
                            //break;
                        }
                    }
                    if ($this->debug) {
                        $this->debugOutput("<h3>SEARCH BY CODE RESULT: ${count}</h3>");
                    }

                    // 2) Search for the entire keyword phrase and its replacements
                    $count = 0;

                    if ($this->resultArrayPos < $this->maximumNumberOfResults) {
                        $fieldArray = [];
                        $keywordPhrase = $this->replaceSearchPhraseOrWord($keywordPhrase);
                        //now we are going to look for synonyms
                        $words = explode(' ', trim(preg_replace('!\s+!', ' ', $keywordPhrase)));
                        foreach ($words as $word) {
                            //todo: why are we looping through words?
                            $keywordPhrase = $this->replaceSearchPhraseOrWord($keywordPhrase, $word);
                        }
                        if ($this->debug) {
                            $this->debugOutput('<pre>WORD ARRAY: ' . print_r($keywordPhrase, 1) . '</pre>');
                        }

                        //work out searches
                        foreach ($this->extraBuyableFieldsToSearchFullText as $tempClassName => $fieldArrayTemp) {
                            if ($singleton instanceof $tempClassName) {
                                $fieldArray = $fieldArrayTemp;
                                break;
                            }
                        }
                        if ($this->debug) {
                            $this->debugOutput('<pre>FIELD ARRAY: ' . print_r($fieldArray, 1) . '</pre>');
                        }

                        $searches = $this->getSearchArrays($keywordPhrase, $fieldArray);

                        //we search exact matches first then other matches ...
                        foreach ($searches as $search) {
                            $list2 = $baseList->where($search);
                            $count = $list2->count();
                            if ($this->debug) {
                                $this->debugOutput("<p>${search}: ${count}</p>");
                            }
                            if ($count > 0) {
                                if ($this->addToResults($list2)) {
                                    break;
                                }
                            }
                            if ($this->resultArrayPos >= $this->maximumNumberOfResults) {
                                break;
                            }
                        }
                    }
                    if ($this->debug) {
                        $this->debugOutput("<h3>FULL KEYWORD SEARCH: ${count}</h3>");
                    }

                    if ($this->debug) {
                        $this->debugOutput('<hr /><h3>PRODUCT GROUP SEARCH</h3>');
                    }
                    // 3) Do the same search for Product Group names
                    $count = 0;
                    if ($limitToCurrentSection) {
                        //cant search other sections in this case...
                    } else {
                        $searches = $this->getSearchArrays($keywordPhrase);
                        if ($this->debug) {
                            $this->debugOutput('<pre>SEARCH ARRAY: ' . print_r($searches, 1) . '</pre>');
                        }

                        foreach ($searches as $search) {
                            $productGroups = ProductGroup::get()->where($search)->filter(['ShowInSearch' => 1]);
                            $count = $productGroups->count();
                            //redirect if we find exactly one match and we have no matches so far...
                            if ($count === 1 && ! $this->resultArrayPos && ! $limitToCurrentSection) {
                                $immediateRedirectLink = $productGroups->First()->Link();
                                $this->debugOutput('<p style="color: red">Found one answer for potential immediate redirect: ' . $immediateRedirectLink . '</p>');
                            }
                            if ($count > 0) {
                                foreach ($productGroups as $productGroup) {
                                    //we add them like this because we like to keep them in order!
                                    if (! in_array($productGroup->ID, $this->productGroupIDs, true)) {
                                        $this->productGroupIDs[] = $productGroup->ID;
                                    }
                                }
                            }
                        }
                        if ($this->debug) {
                            $this->debugOutput("<h3>PRODUCT GROUP SEARCH: ${count}</h3>");
                        }
                    }
                }
            }
        }
        if (! $isKeywordSearch) {
            $this->addToResults($baseList);
        }

        $redirectToPage = null;

        if (!$limitToCurrentSection) {
            $redirectToPage = ProductGroupSearchPage::get()->first();
        }

        if (!$redirectToPage) {
            // for section specific search,
            // redirect to the specific section (basically where we came from)
            $redirectToPage = $this->controller->dataRecord;
        }

        if ($searchHistoryObject) {
            $searchHistoryObject->ProductCount = count($this->resultArray);
            $searchHistoryObject->GroupCount = count($this->productGroupIDs);
            $searchHistoryObject->write();
        }

        if ($immediateRedirectLink) {
            $link = $immediateRedirectLink;
        } else {
            $link = $redirectToPage->Link($this->controllerSearchResultDisplayMethod);
        }

        if ($this->additionalGetParameters) {
            $link .= '?' . $this->additionalGetParameters;
        }

        $this->controller->redirect($link);
    }

    /**
     * saves the form into session.
     *
     * @param array $data - data from form (OPTIONAL)
     */
    public function saveDataToSession($data = null)
    {
        if (!is_array($data)) {
            $data = $this->getData();
        }

        if (isset($data['MinimumPrice']) && ! $data['MinimumPrice']) {
            unset($data['MinimumPrice']);
        }

        if (isset($data['MaximumPrice']) && ! $data['MaximumPrice']) {
            unset($data['MaximumPrice']);
        }

        if (isset($data['ShortKeyword']) && $data['ShortKeyword']) {
            $data['Keyword'] = $data['ShortKeyword'];
        }

        if (isset($data['Keyword']) && $data['Keyword']) {
            $data['ShortKeyword'] = $data['Keyword'];
        }

        Controller::curr()->getRequest()->getSession()->set($this->Config()->get('form_data_session_variable'), $data);
    }

    /**
     * Creates three levels of searches that can be executed one after the
     * other, each being less specific than the last.
     *
     * returns true when done and false when more are needed
     *
     * @return bool
     */
    protected function addToResults($listToAdd)
    {
        $internalItemID = 0;
        $listToAdd = $listToAdd->limit($this->maximumNumberOfResults - $this->resultArrayPos);
        $listToAdd = $listToAdd->sort('Price', 'DESC');
        foreach ($listToAdd as $page) {
            $id = $page->IDForSearchResults();
            if ($this->debug) {
                $internalItemID = $page->InternalItemIDForSearchResults();
            }
            if ($id) {
                if (! in_array($id, $this->resultArray, true)) {
                    ++$this->resultArrayPos;
                    $this->resultArray[$this->resultArrayPos] = $id;
                    if ($this->debug) {
                        $this->resultArrayPerIternalItemID[$this->resultArrayPos] = $internalItemID;
                    }
                    if ($this->resultArrayPos > $this->maximumNumberOfResults) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * creates three levels of searches that
     * can be executed one after the other, each
     * being less specific than the last...
     *
     * @param array $keywordPhrase  - words being search
     * @param array $fields - fields being searched
     *
     * @return array
     */
    protected function getSearchArrays($keywordPhrase, $fields = ['Title', 'MenuTitle'])
    {
        //make three levels of search
        $searches = [];
        $wordsAsString = preg_replace('!\s+!', ' ', $keywordPhrase);
        $wordAsArray = explode(' ', $wordsAsString);
        $hasWordArray = false;
        $searchStringAND = '';
        if (count($wordAsArray) > 1) {
            $hasWordArray = true;
            $searchStringArray = [];
            foreach ($wordAsArray as $word) {
                $searchStringArray[] = "LOWER(\"FFFFFF\") LIKE '%${word}%'";
            }
            $searchStringAND = '(' . implode(' AND ', $searchStringArray) . ')';
            // $searchStringOR = '('.implode(' OR ', $searchStringArray).')';
        }
        // $wordsAsLikeString = trim(implode('%', $wordAsArray));
        $completed = [];
        $count = -1;
        if (in_array('Title', $fields, true)) {
            if ($hasWordArray) {
                $searches[++$count][] = str_replace('FFFFFF', 'Title', $searchStringAND); // d) Words matched individually
                // $searches[++$count + 100][] = str_replace('FFFFFF', 'Title', $searchStringOR); // d) Words matched individually
            }
            $completed['Title'] = 'Title';
        }
        if (in_array('MenuTitle', $fields, true)) {
            $searches[++$count][] = "LOWER(\"MenuTitle\") = '${wordsAsString}'"; // a) Exact match
            $searches[++$count][] = "LOWER(\"MenuTitle\") LIKE '%${wordsAsString}%'"; // b) Full match within a bigger string
            if ($hasWordArray) {
                $searches[++$count][] = str_replace('FFFFFF', 'MenuTitle', $searchStringAND); // d) Words matched individually
                // $searches[++$count + 100][] = str_replace('FFFFFF', 'MenuTitle', $searchStringOR); // d) Words matched individually
            }
            $completed['MenuTitle'] = 'MenuTitle';
        }
        if (in_array('MetaTitle', $fields, true)) {
            $searches[++$count][] = "LOWER(\"MetaTitle\") = '${wordsAsString}'"; // a) Exact match
            $searches[++$count][] = "LOWER(\"MetaTitle\") LIKE '%${wordsAsString}%'"; // b) Full match within a bigger string
            if ($hasWordArray) {
                $searches[++$count][] = str_replace('FFFFFF', 'MetaTitle', $searchStringAND); // d) Words matched individually
                // $searches[++$count + 100][] = str_replace('FFFFFF', 'MetaTitle', $searchStringOR); // d) Words matched individually
            }
            $completed['MetaTitle'] = 'MetaTitle';
        }
        foreach ($fields as $field) {
            if (! isset($completed[$field])) {
                $searches[++$count][] = "LOWER(\"${field}\") = '${wordsAsString}'"; // a) Exact match
                $searches[++$count][] = "LOWER(\"${field}\") LIKE '%${wordsAsString}%'"; // b) Full match within a bigger string
                if ($hasWordArray) {
                    $searches[++$count][] = str_replace('FFFFFF', $field, $searchStringAND); // d) Words matched individually
                    // $searches[++$count + 100][] = str_replace('FFFFFF', $field, $searchStringOR); // d) Words matched individually
                }
            }
        }
        ksort($searches);
        $returnArray = [];
        foreach ($searches as $key => $search) {
            $returnArray[$key] = implode(' OR ', $search);
        }

        return $returnArray;
    }

    /**
     * @param  string $keywordPhrase
     * @param  string $word (optional word within keywordPhrase)
     *
     * @return string (updated Keyword Phrase)
     */
    protected function replaceSearchPhraseOrWord($keywordPhrase, $word = '')
    {
        if (! $word) {
            $word = $keywordPhrase;
        }
        $replacements = SearchReplacement::get()
            ->where(
                "
                LOWER(\"Search\") = '${word}' OR
                LOWER(\"Search\") LIKE '%,${word}' OR
                LOWER(\"Search\") LIKE '${word},%' OR
                LOWER(\"Search\") LIKE '%,${word},%'"
            );
        //if it is a word replacement then we do not want replace whole phrase ones ...
        if ($keywordPhrase !== $word) {
            $replacements = $replacements->exclude(['ReplaceWholePhrase' => 1]);
        }
        if ($replacements->count()) {
            $replacementsArray = $replacements->map('ID', 'Replace')->toArray();
            if ($this->debug) {
                $this->debugOutput("found alias for ${word}");
            }
            foreach ($replacementsArray as $replacementWord) {
                $keywordPhrase = str_replace($word, $replacementWord, $keywordPhrase);
            }
        }

        return $keywordPhrase;
    }

    /**
     * Returns all the {@link ProductGroups} which are matched in this search
     * form.
     *
     */
    public function getProductGroups(): DataList
    {
        // @todo
        return ProductGroup::get();
    }

    /**
     * @return array
     */
    public function getSessionData(): array
    {
        $data = Controller::curr()->getRequest()->getSession()->get(
            Config::inst()->get(ProductSearchForm::class, 'form_data_session_variable')
        );

        if (!$data) {
            return [];
        }

        return $data;
    }
}
