<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Settings;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;

use SilverStripe\Core\Convert;

use SilverStripe\Core\Injector\Injectable;

use SilverStripe\ORM\ArrayList;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * keeps track of the current settings for FILTER / SORT / DISPLAY for user
 */


class UserPreference
{
    use Configurable;
    use Injectable;

    public const GET_VAR_VALUE_PLACE_HOLDER = '[[INSERT_VALUE_HERE]]';

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
    protected $controller = null;

    protected $dataRecord = null;

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
     * @param  bool $useSession  [description]
     * @return self              [description]
     */
    public function setUseSession(?bool $useSession): self
    {
        $this->useSession = $useSession;

        return $this;
    }

    /**
     * @param  HTTPRequest  $request  [description]
     * @return self                      [description]
     */
    public function setRequest($request): self
    {
        if (! $request instanceof HTTPRequest) {
            user_error('Please make sure that you provide an instance of a HTTPRequest ' . print_r($request, 1));
        }
        $this->request = $request;

        return $this;
    }

    /**
     * @param  ContentController  $controller
     * @return self
     */
    public function setController($controller): self
    {
        if (! $controller instanceof ContentController) {
            user_error('Please make sure that you provide an instance of a content controller ' . print_r($controller, 1));
        }
        $this->controller = $controller;

        return $this;
    }

    /**
     * @param  ProductGroup       $dataRecord [description]
     * @return self               [description]
     */
    public function setDataRecord($dataRecord): self
    {
        if (! $dataRecord instanceof ContentController) {
            user_error('Please make sure that you provide an instance of a ProductGroup ' . print_r($dataRecord, 1));
        }
        $this->dataRecord = $dataRecord;

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
            $pageStart = $this->controller->getCurrentPageNumber();
        }
        $pageId = 0;
        if ($this->useSessionPerPage) {
            $pageId = $this->dataRecord->ID;
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

            if ($this->controller->IsSearchResults()) {
                $count = $this->controller->getProductList()->getRawCount();

                if ($count) {
                    $toAdd = $count . ' ' . _t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                    $secondaryTitle .= $this->addToTitle($toAdd);
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->addToTitle($toAdd);
                }
            }

            if ($this->controller->HasFilter()) {
                $secondaryTitle .= $this->addToTitle($this->controller->getCurrentFilterTitle());
            }

            if ($this->controller->HasSort()) {
                $secondaryTitle .= $this->addToTitle($this->controller->getCurrentSortTitle());
            }

            $currentPageNumber = $this->controller->getCurrentPageNumber();
            if ($currentPageNumber > 1) {
                $secondaryTitle .= $this->addToTitle(_t('ProductGroup.PAGE', 'Page') . ' ' . $page);
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

    /**
     * returns the current page with get variables. If a type is specified then
     * instead of the value for that type, we add: '[[INSERT_HERE]]'
     * @param  string $type [description]
     * @return string       [description]
     */
    public function getLinkTemplate(?string $type = '', ?string $action = null, ?string $replacementForType = ''): string
    {
        $base = $this->dataRecord->Link($action);
        $getVars = [];
        foreach ($this->controller->getSortFilterDisplayNames() as $key => $values) {
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

    public function getBestKeyAndValidateKey($type, $key)
    {
        return $key;
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
        $sortFilterDisplayNames = $this->controller->getSortFilterDisplayNames();

        foreach ($sortFilterDisplayNames as $type => $oneTypeArray) {
            $getVariableName = $oneTypeArray['getVariable'];
            if (isset($overrideArray[$getVariableName])) {
                $newPreference = $overrideArray[$getVariableName];
            } elseif (! isset($this->userPreferences[$type])) {
                $newPreference = $this->request->getVar($getVariableName);
            }
            if ($newPreference) {
                $this->userPreferences[$type] = $newPreference;
            }
            if ($this->useSession) {
                $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
                if ($this->useSessionPerPage) {
                    $sessionName .= '_' . $this->dataRecord->ID;
                }
                $sessionValue = $this->controller->request->getSession()->set('ProductGroup_' . $sessionName, $newPreference);
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
     *
     * @todo: move to controller?
     */
    protected function getCurrentUserPreferences($type)
    {
        $key = '';
        if ($this->useSession) {
            $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
            if ($this->useSessionPerPage) {
                $sessionName .= '_' . $this->dataRecord->ID;
            }
            $sessionValue = $this->request->getSession()->get('ProductGroup_' . $sessionName);
            $key = Convert::raw2sql($sessionValue);
        }
        if (! $key) {
            $key = $this->userPreferences[$type];
        }
        if (! $key) {
            $key = $this->dataRecord->getListConfigCalculated($type);
        }
        return $this->getBestKeyAndValidateKey($type, $key);
    }

    /**
     * Provides a dataset of links for a particular user preference.
     *
     * @param string $type        SORT | FILTER | DISPLAY - e.g. sort_options
     *
     * @return ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
     */
    protected function userPreferencesLinks($type)
    {
        // get basics
        $sortFilterDisplayNames = $this->controller->getSortFilterDisplayNames();
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
     * @param string $title
     */
    protected function addTitleToField(string $field, string $title)
    {
        if (isset($this->controller->{$field})) {
            $this->controller->{$field} .= $secondaryTitle;
        }
    }

    /**
     * @param  array $idArray         optional array of IDs to sort by
     * @param  mixed $alternativeSort optional alternative sort
     * @return mixed
     */
    protected function getSearchResultsDefaultSort(?array $idArray = [], $alternativeSort = null)
    {
        if ($alternativeSort) {
            return $alternativeSort;
        }
        $sortGetVariable = $this->controller->getSortFilterDisplayNames('SORT', 'getVariable');
        if (! $this->request->getVar($sortGetVariable)) {
            $suggestion = Config::inst()->get(ProductGroupSearchPage::class, 'best_match_key');
            if ($suggestion) {
                $this->saveUserPreferences(['SORT' => ['type' => $suggestion, 'value' => $idArray]]);
            }
        }
    }

    protected function IsShowFullList(): bool
    {
        return $this->controller->getSortFilterDisplayNames('SORT', 'isFullListVariable') ? true : false;
    }
}
