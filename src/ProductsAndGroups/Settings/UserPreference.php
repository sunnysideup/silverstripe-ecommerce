<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Settings;

/**
 * keeps track of the current settings for FILTER / SORT / DISPLAY for user
 */


class UserPreference
{
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
            $getVariableNameFilter . '=' . $this->getProductListConfigDefaultValue('FILTER') . $ampersand .
            $getVariableNameSort . '=' . $this->getProductListConfigDefaultValue('SORT') . $ampersand .
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
     * 2. getProductListConfigDefaultValue.
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
            $key = $this->getProductListConfigDefaultValue($type);
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
}
