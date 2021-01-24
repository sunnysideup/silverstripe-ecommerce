<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Settings;

/**
 * keeps track of the current settings for FILTER / SORT / DISPLAY for user
 */


class UserPreference
{

    protected $request = null;

    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }
    /**
     * Link that returns a list of all the products for this product group as a
     * simple list. It resets everything; not just filter.
     *
     * @param bool $escapedAmpersands
     *
     * @return string
     */
    public function ResetPreferencesLink($escapedAmpersands = true)
    {
        $ampersand = '&';
        if ($escapedAmpersands) {
            $ampersand = '&amp;';
        }
        $getVariableNameFilter = $this->getSortFilterDisplayNames('FILTER', 'getVariable');
        $getVariableNameSort = $this->getSortFilterDisplayNames('SORT', 'getVariable');

        return $this->Link() . '?' .
            $getVariableNameFilter . '=' . $this->getListConfigCalculated('FILTER') . $ampersand .
            $getVariableNameSort . '=' . $this->getListConfigCalculated('SORT') . $ampersand .
            'reload=1';
    }

    /**
     * Checks out a bunch of $_GET variables that are used to work out user
     * preferences.
     *
     * Some of these are saved to session.
     *
     * @param array $overrideArray - override $_GET variable settings
     */
    protected function saveUserPreferences($request, $overrideArray = [])
    {
        $sortFilterDisplayNames = $this->getSortFilterDisplayNames();

        foreach ($sortFilterDisplayNames as $type => $oneTypeArray) {
            $getVariableName = $oneTypeArray['getVariable'];

            if (isset($overrideArray[$getVariableName])) {
                $newPreference = $overrideArray[$getVariableName];
            } else {
                $newPreference = $request->getVar($getVariableName);
            }

            if ($newPreference) {
                $optionsVariableName = $oneTypeArray['configName'];
                $options = EcommerceConfig::get($this->ClassName, $optionsVariableName);
                if (isset($options[$newPreference])) {
                    $this->getRequest()->getSession()->set('ProductGroup_' . $sessionName, $newPreference);
                    //save in model as well...
                }
            } else {
                $newPreference = $this->getRequest()->getSession()->get('ProductGroup_' . $sessionName);
            }

            if ($newPreference) {
                $this->setCurrentUserPreference($type, $newPreference);
            }
        }
    }

    /**
     * Returns the Title for a type key.
     *
     * If no key is provided then the default key is used.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string
     */
    protected function getUserPreferencesTitle($type)
    {
        $method = 'get' . $this->getSortFilterDisplayNames($type, 'dbFieldName') . 'Title';
        $value = $this->getFinalProductList()->{$method}();
        if ($value) {
            return $value;
        }

        return _t('ProductGroup.UNKNOWN', 'UNKNOWN USER SETTING');
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
        $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
        if ($sessionValue = $this->getRequest()->getSession()->get('ProductGroup_' . $sessionName)) {
            $key = Convert::raw2sql($sessionValue);
        } else {
            $key = $this->getListConfigCalculated($type);
        }
        return $this->getBestKeyAndValidateKey($type, $key);
    }

    /**
     * Provides a dataset of links for a particular user preference.
     *
     * @param string $type SORT | FILTER | DISPLAY - e.g. sort_options
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
     */
    protected function userPreferencesLinks($type)
    {
        // get basics
        $sortFilterDisplayNames = $this->getSortFilterDisplayNames();
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
     * Add a secondary title to the main title in case there is, for example, a
     * filter applied (e.g. Socks | MyBrand).
     *
     * @param string $secondaryTitle
     */
    protected function addSecondaryTitle($secondaryTitle = '')
    {
        //todo: add to config

        if (! $this->secondaryTitleHasBeenAdded) {
            if (trim($secondaryTitle)) {
                $secondaryTitle = $this->prepareForSecondaryTitleAddition($secondaryTitle);
            }

            if ($this->IsSearchResults()) {
                $count = $this->getProductList()->getRawCount();

                if ($count) {
                    $toAdd = $count . ' ' . _t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                    $secondaryTitle .= $this->prepareForSecondaryTitleAddition($toAdd);
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->prepareForSecondaryTitleAddition($toAdd);
                }
            }

            if ($this->hasFilter()) {
                $secondaryTitle .= $this->prepareForSecondaryTitleAddition($this->getCurrentFilterTitle());
            }

            if ($this->HasSort()) {
                $secondaryTitle .= $this->prepareForSecondaryTitleAddition($this->getCurrentSortTitle());
            }

            $currentPageNumber = $this->getCurrentPageNumber();
            if ($currentPageNumber > 1) {
                $secondaryTitle .= $this->prepareForSecondaryTitleAddition(
                    $pipe,
                    _t('ProductGroup.PAGE', 'Page') . ' ' . $page
                );
            }

            if ($secondaryTitle) {
                $this->Title .= $secondaryTitle;

                if (isset($this->MetaTitle)) {
                    $this->MetaTitle .= $secondaryTitle;
                }

                if (isset($this->MetaDescription)) {
                    $this->MetaDescription .= $secondaryTitle;
                }
            }

            // dont update menu title, because the entry in the menu
            // should stay the same as it links back to the unfiltered
            // page (in some cases).

            $this->secondaryTitleHasBeenAdded = true;
        }
    }

    /**
     * removes any spaces from the 'toAdd' bit and adds the pipe if there is
     * anything to add at all.  Through the lang files, you can change the pipe
     * symbol to anything you like.
     *
     * @param  string $toAdd
     * @return string
     */
    protected function prepareForSecondaryTitleAddition(string $toAdd): string
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
     * returns the current page with get variables. If a type is specified then
     * instead of the value for that type, we add: '[[INSERT_HERE]]'
     * @param  string $type [description]
     * @return string       [description]
     */
    protected function getLinkTemplate(?string $type = '', ?string $action = null): string
    {
        $base = $this->dataRecord->Link($action);
        $getVars = [];
        foreach ($this->getSortFilterDisplayNames() as $key => $values) {
            if ($type && $type === $key) {
                $value = self::GET_VAR_VALUE_PLACE_HOLDER;
            } else {
                $value = $this->getCurrentUserPreferences($type);
            }
            $getVars[$values['getVariable']] = $value;
        }

        return $base . '?' . http_build_query($getVars);
    }

    protected function getSearchResultsDefaultSort($idArray, $alternativeSort = null)
    {
        if (! $alternativeSort) {
            $sortGetVariable = $this->getSortFilterDisplayNames('SORT', 'getVariable');
            if (! $this->request->getVar($sortGetVariable)) {
                $suggestion = Config::inst()->get(ProductGroupSearchPage::class, 'best_match_key');
                if ($suggestion) {
                    $this->saveUserPreferences(['SORT' => $suggestion]);
                }
            }
        }
        return $alternativeSort;
    }

    protected function IsShowFullList(): bool
    {
        //to be completed
    }

    /**
     * Unique caching key for the product list...
     *
     * @return string | Null
     */
    public function ProductGroupListCachingKey(?bool $withPageNumber = false, ?string $additionalKey = ''): string
    {
        $filterKey = $this->getCurrentUserPreferences('FILTER');
        $displayKey = $this->getCurrentUserPreferences('DISPLAY');
        $sortKey = $this->getCurrentUserPreferences('SORT');
        $pageStart = '';
        if ($withPageNumber) {
            $pageStart = $this->getCurrentPageNumber();
        }
        return $this->cacheKey(
            implode(
                '_',
                array_filter([
                    $displayKey,
                    $filterKey,
                    $sortKey,
                    $pageStart,
                    $additionalKey,
                ])
            )
        );
    }
}
