<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Settings;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

use SilverStripe\Core\Convert;

use SilverStripe\Core\Injector\Injectable;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;
use Sunnysideup\Ecommerce\ProductsAndGroups\Template;

/**
 * keeps track of the current settings for FILTER / SORT / DISPLAY for user
 * the associated links and all that sort of stuff.
 */
class UserPreference
{
    use Configurable;
    use Injectable;

    protected const GET_VAR_VALUE_PLACE_HOLDER = '[[INSERT_VALUE_HERE]]';

    /**
     * variable to make sure secondary title only gets
     * added once.
     *
     * We are talking here about the ProductGroupController Page name to which we add stuff like
     * "filtered for FOO BAR"
     *
     * @var bool
     */
    protected $secondaryTitleHasBeenAdded = false;

    /**
     * keep a store for every page setting?
     * For example, do we store in session how a particular page is filtered / sorted
     *
     * @var bool
     */
    protected $useSessionPerPage = false;

    /**
     * keep a store for every FILTER|SORT|DISPLAY setting?
     * @var bool
     */
    protected $useSession = false;

    /**
     * @var HTTPRequest|null
     */
    protected $request = null;

    /**
     * @var ContentController|null
     */
    protected $rootGroupController = null;

    protected $rootGroup = null;

    /**
     * here is where we save the GET variables and any other settings for FILTER|SORT|DISPLAY
     * @var array
     */
    protected $userPreferences = [];

    /**
     * @param  bool $useSessionPerPage
     * @return self
     */
    public function setUseSessionPerPage(?bool $useSessionPerPage): self
    {
        $this->useSessionPerPage = $useSessionPerPage;

        return $this;
    }

    /**
     * @param  bool $useSession
     * @return self
     */
    public function setUseSession(?bool $useSession): self
    {
        $this->useSession = $useSession;

        return $this;
    }

    /**
     * @param  HTTPRequest  $request
     * @return self
     */
    public function setRequest($request): self
    {
        ClassHelpers::check_for_instance_of($request, HTTPRequest::class, true);
        $this->request = $request;

        return $this;
    }

    /**
     * @param  ProductGroupController  $rootGroupController
     * @return self
     */
    public function setController($rootGroupController): self
    {
        ClassHelpers::check_for_instance_of($rootGroupController, ProductGroupController::class, true);
        $this->rootGroupController = $rootGroupController;

        return $this;
    }

    /**
     * @param  ProductGroup       $rootGroup
     * @return self
     */
    public function setRootGroup($rootGroup): self
    {
        ClassHelpers::check_for_instance_of($rootGroup, ProductGroup::class, true);

        $this->rootGroup = $rootGroup;

        return $this;
    }

    /**
     * Unique caching key for the product list...
     *
     * @return string | Null
     */
    public function ProductGroupListCachingKey(?bool $withPageNumber = false, ?string $additionalKey = ''): string
    {
        $pageStart = '';
        if ($withPageNumber) {
            $pageStart = $this->rootGroupController->getCurrentPageNumber();
        }
        $pageId = 0;
        if ($this->useSessionPerPage) {
            $pageId = $this->rootGroup->ID;
        }
        return $this->cacheKey(
            implode(
                '_',
                [
                    $this->getCurrentUserPreferences('SORT'),
                    $this->getCurrentUserPreferences('FILTER'),
                    $this->getCurrentUserPreferences('DISPLAY'),
                    $pageStart,
                    $additionalKey,
                    $pageId,
                ]
            )
        );
    }

    /**
     * Add a secondary title to the main title in case there is, for example, a
     * filter applied (e.g. Socks | MyBrand).
     *
     * @param string $secondaryTitle
     */
    public function addSecondaryTitle(?string $secondaryTitle = '')
    {
        //todo: add to config

        if (! $this->secondaryTitleHasBeenAdded) {
            if (trim($secondaryTitle)) {
                $secondaryTitle = $this->addToTitle($secondaryTitle);
            }

            if ($this->rootGroupController->IsSearchResults()) {
                $count = $this->rootGroupController->getProductList()->getRawCount();

                if ($count) {
                    $toAdd = $count . ' ' . _t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                    $secondaryTitle .= $this->addToTitle($toAdd);
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->addToTitle($toAdd);
                }
            }

            if ($this->rootGroupController->HasFilter()) {
                $secondaryTitle .= $this->addToTitle($this->rootGroupController->getCurrentFilterTitle());
            }

            if ($this->rootGroupController->HasSort()) {
                $secondaryTitle .= $this->addToTitle($this->rootGroupController->getCurrentSortTitle());
            }

            $currentPageNumber = $this->rootGroupController->getCurrentPageNumber();
            if ($currentPageNumber > 1) {
                $secondaryTitle .= $this->addToTitle(_t('ProductGroup.PAGE', 'Page') . ' ' . $currentPageNumber);
            }

            if ($secondaryTitle) {
                $this->addTitleToField('Title', $secondaryTitle);
                $this->addTitleToField('MetaTitle', $secondaryTitle);
                $this->addTitleToField('MetaDescription', $secondaryTitle);
            }

            // dont update menu title, because the entry in the menu
            // should stay the same as it links back to the unfiltered
            // page (in some cases).

            $this->secondaryTitleHasBeenAdded = true;
        }
    }

    public function getBestKeyAndValidateKey($type, $key)
    {
        return $key;
    }

    /**
     * Provides a dataset of links for a particular user preference.
     *
     * @param string $type        SORT | FILTER | DISPLAY - e.g. sort_options
     *
     * @return ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
     */
    public function getUserPreferencesLinks($type)
    {
        // get basics
        $sortFilterDisplayNames = $this->rootGroupController->getSortFilterDisplayValues();
        $options = $this->getConfigOptions($type);

        // if there is only one option then do not bother
        if (count($options) < 2) {
            return;
        }

        // get more config names
        $translationCode = $sortFilterDisplayNames[$type]['translationCode'];
        $getVariableName = $sortFilterDisplayNames[$type]['getVariable'];
        $arrayList = ArrayList::create();

        if (count($options)) {
            foreach ($options as $key => $array) {
                $link = '?' . $getVariableName . "=${key}";
                user_error('redo with link template');
                $link = $this->Link() . $link;

                $arrayList->push(ArrayData::create([
                    'Name' => _t('ProductGroup.' . $translationCode . strtoupper(str_replace(' ', '', $array['Title'])), $array['Title']),
                    'Link' => $link,
                    'SelectKey' => $key,
                ]));
            }
        }

        return $arrayList;
    }

    /**
     * full list of options with Links that know about "current"
     * @param  string    $type (FILTER|SORT|DISPLAY)
     * @param  string    $currentKey
     * @param  boolean   $ajaxify
     *
     * @return ArrayList
     */
    public function getLinksPerType(string $type, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        $list = new ArrayList();
        if (! $currentKey) {
            $currentKey = $this->getCurrentUserPreferences($type);
        }
        $options = $this->getTemplateForProductsAndGroups()->getOptions($type);
        if (! empty($options)) {
            foreach ($options as $key => $arrayData) {
                $isCurrent = $currentKey === $key;
                $obj = new ArrayData(
                    [
                        'Title' => $arrayData['Title'],
                        'Current' => $isCurrent ? true : false,
                        //todo: fix this!!!!
                        'Link' => $this->getLinkTemplate(null, $type, $key),
                        'LinkingMode' => $isCurrent ? 'current' : 'link',
                        'Ajaxify' => $ajaxify,
                    ]
                );
                $list->push($obj);
            }
        }

        return $list;
    }

    /**
     * returns the current page with get variables. If a type is specified then
     * instead of the value for that type, we add: '[[INSERT_HERE]]'
     * @param  string $action                optional
     * @param  string $type                  optional
     * @param  string $replacementForType    optional - what you would like the type be instead! - e.g. for FILTER I'd like it to be "somethingelse"
     *
     * @return string
     */
    public function getLinkTemplate(?string $action = null, ?string $type = '', ?string $replacementForType = ''): string
    {
        $base = $this->rootGroup->Link($action);
        $getVars = [];
        foreach ($this->rootGroupController->getSortFilterDisplayValues() as $key => $values) {
            if ($type && $type === $key) {
                if ($replacementForType) {
                    $value = $replacementForType;
                } else {
                    $value = self::GET_VAR_VALUE_PLACE_HOLDER;
                }
            } else {
                $value = $this->getCurrentUserPreferences($type);
            }
            $getVars[$values['getVariable']] = $value;
        }

        return $base . '?' . http_build_query($getVars);
    }

    /**
     * TODO: move this to a better place!
     * @param  array $idArray         optional array of IDs to sort by
     * @param  mixed $alternativeSort optional alternative sort
     * @return mixed                  returns null|array|string
     */
    public function getSearchResultsDefaultSort(?array $idArray = [], $alternativeSort = null)
    {
        $array = null;
        if ($alternativeSort) {
            $array = $alternativeSort;
        }
        $sortGetVariable = $this->rootGroupController->getSortFilterDisplayValues('SORT', 'getVariable');
        if (! $this->request->getVar($sortGetVariable)) {
            $suggestion = Config::inst()->get(ProductGroupSearchPage::class, 'best_match_key');
            if ($suggestion) {
                $array = ['SORT' => ['type' => $suggestion, 'value' => $idArray]];
            }
        }

        if ($array) {
            $this->saveUserPreferences($array);
        }
        return $array;
    }

    /**
     * special case of full list.
     * @return bool
     */
    public function IsShowFullList(): bool
    {
        return $this->getTemplateForProductsAndGroups()
            ->getApplyer('DISPLAY')
            ->IsShowFullList($this->getCurrentUserPreferences('DISPLAY')) ? true : false;
    }

    /**
     * Checks out a bunch of $_GET variables that are used to work out user
     * preferences.
     *
     * Some of these are saved to session.
     *
     * @param array $overrideArray - optional - override $_GET variable settings
     * an array can be like this:
     * ```php
     *     FILTER => 'abc'
     * ```
     * OR
     * ```php
     *     FILTER => ['type' => 'foo', 'value' => 'bar']
     * ```
     * @return self
     */
    protected function saveUserPreferences(?array $overrideArray = []): self
    {
        $sortFilterDisplayNames = $this->rootGroupController->getSortFilterDisplayValues();

        foreach ($sortFilterDisplayNames as $type => $oneTypeArray) {
            $getVariableName = $oneTypeArray['getVariable'];
            if (isset($overrideArray[$getVariableName])) {
                $newPreference = $overrideArray[$getVariableName];
            } elseif (! isset($this->userPreferences[$type])) {
                $newPreference = $this->request->getVar($getVariableName);
            } else {
                $newPreference = $this->userPreferences[$type];
            }
            $this->userPreferences[$type] = $newPreference;
            if ($this->useSession) {
                $sessionName = $this->getSortFilterDisplayValues($type, 'sessionName');
                if ($this->useSessionPerPage) {
                    $sessionName .= '_' . $this->rootGroup->ID;
                }
                $this->rootGroupController->request->getSession()->set('ProductGroup_' . $sessionName, $newPreference);
            }
        }

        return $this;
    }

    /**
     * Checks for the most applicable user preferences for this user:
     * 1. session value
     * 2. getListConfigCalculated.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string
     */
    protected function getCurrentUserPreferences(?string $type = '')
    {
        if (! $type) {
            return [
                'FILTER' => $this->getCurrentUserPreferences('FILTER'),
                'SORT' => $this->getCurrentUserPreferences('SORT'),
                'DISPLAY' => $this->getCurrentUserPreferences('DISPLAY'),
            ];
        }
        $key = '';
        if ($this->useSession) {
            $sessionName = $this->getSortFilterDisplayValues($type, 'sessionName');
            if ($this->useSessionPerPage) {
                $sessionName .= '_' . $this->rootGroup->ID;
            }
            $sessionValue = $this->request->getSession()->get('ProductGroup_' . $sessionName);
            $key = Convert::raw2sql($sessionValue);
        }
        if (! $key) {
            $key = $this->userPreferences[$type];
        }
        if (! $key) {
            $key = $this->rootGroup->getListConfigCalculated($type);
        }
        return $this->getBestKeyAndValidateKey($type, $key);
    }

    /**
     * removes any spaces from the 'toAdd' bit and adds the pipe if there is
     * anything to add at all.  Through the lang files, you can change the pipe
     * symbol to anything you like.
     *
     * @param  string $toAdd the string to add
     * @return string the string to add, cleaned up and with prefix and so on added.
     */
    protected function addToTitle(string $toAdd): string
    {
        $toAdd = trim($toAdd);
        $length = strlen($toAdd);

        if ($length > 0) {
            $pipe = _t('ProductGroup.TITLE_SEPARATOR', ' | ');
            $toAdd = $pipe . $toAdd;
        }

        return $toAdd;
    }

    /**
     * @param string $field
     * @param string $secondaryTitle
     *
     * @return self
     */
    protected function addTitleToField(string $field, string $secondaryTitle): self
    {
        if (! empty($this->rootGroupController->{$field})) {
            $this->rootGroupController->{$field} .= $secondaryTitle;
        }

        return $this;
    }

    /**
     * @return Template
     */
    protected function getTemplateForProductsAndGroups()
    {
        $obj = $this->rootGroup->getTemplateForProductsAndGroups();
        ClassHelpers::check_for_instance_of($obj, Template::class, true);

        return $obj;
    }
}
