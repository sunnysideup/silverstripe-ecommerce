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
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductGroupFilter;

use Sunnysideup\Ecommerce\ProductsAndGroups\Template;

use Sunnysideup\Vardump\DebugTrait;

/**
 * keeps track of the  settings for FILTER / SORT / DISPLAY for user
 * the associated links and all that sort of stuff.
 */
class UserPreference
{
    use Configurable;
    use Injectable;
    use DebugTrait;

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
     * @var bool
     */
    protected $useSession = [
        'FILTER' => false,
        'SORT' => false,
        'DISPLAY' => false,
    ];

    /**
     * @var bool
     */
    protected $useSessionPerPage = [
        'FILTER' => false,
        'SORT' => false,
        'DISPLAY' => false,
    ];

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
     * keep a store for every page setting?
     * For example, do we store in session how a particular page is filtered / sorted
     *
     * @var bool
     */
    private static $use_session_per_page = [];

    /**
     * keep a store for every FILTER|SORT|DISPLAY setting?
     * @var bool
     */
    private static $use_session = [];

    /**
     * @param  bool $useSession
     * @return self
     */
    public function setUseSession(string $type, ?bool $useSession): self
    {
        $this->useSession[$type] = $useSession;

        return $this;
    }

    public function getUseSession(string $type): bool
    {
        $config = $this->Config()->get('use_session');

        return $this->useSession[$type] ?? $config[$type] ?? false;
    }

    public function getUseSessionAll(): array
    {
        $array = [];
        $types = array_keys($this->getTemplateForProductsAndGroups()->getData());
        foreach ($types as $type) {
            $array[$type] = $this->getUseSession($type);
        }
        return $array;
    }

    /**
     * @param  bool $useSessionPerPage
     * @return self
     */
    public function setUseSessionPerPage(string $type, ?bool $useSessionPerPage): self
    {
        $this->useSessionPerPage[$type] = $useSessionPerPage;

        return $this;
    }

    public function getUseSessionPerPage(string $type): bool
    {
        $config = $this->Config()->get('use_session_per_page');

        return $this->useSessionPerPage[$type] ?? $config[$type] ?? false;
    }

    public function getUseSessionPerPageAll(): array
    {
        $array = [];
        $types = array_keys($this->getTemplateForProductsAndGroups()->getData());
        foreach ($types as $type) {
            $array[$type] = $this->getUseSessionPerPage($type);
        }
        return $array;
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
    public function setRootGroupController($rootGroupController): self
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
     *     FILTER => ['key' => 'foo', 'params' => 'bar', 'title' => 'foo bar']
     * ```
     * @return self
     */
    public function saveUserPreferences(?array $overrideArray = []): self
    {
        $sortFilterDisplayNames = $this->rootGroupController->getSortFilterDisplayValues();

        foreach ($sortFilterDisplayNames as $type => $oneTypeArray) {
            $getVariableName = $oneTypeArray['getVariable'];
            if (isset($overrideArray[$type])) {
                $newPreference = $overrideArray[$type];
            } elseif (! isset($this->userPreferences[$type])) {
                $newPreference = $this->request->getVar($getVariableName);
            } else {
                $newPreference = $this->userPreferences[$type];
            }
            $this->userPreferences[$type] = $newPreference;
            //save preference to session
            $this->savePreferenceToSession($type, $newPreference);
        }

        return $this;
    }

    protected function savePreferenceToSession($type, $newPreference)
    {
        if ($this->getUseSession($type)) {
            $sessionName = $this->getSortFilterDisplayValues($type, 'sessionName');
            if ($this->getUseSessionPerPage($type)) {
                $sessionName .= '_' . $this->rootGroup->ID;
            }
            $this->getSession()->set('ProductGroup_' . $sessionName, $newPreference);
        }
    }

    protected function getSession()
    {
        return $this->rootGroupController->request->getSession();
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
    public function getCurrentUserPreferences(?string $type = '')
    {
        if (! $type) {
            $types = array_keys($this->getTemplateForProductsAndGroups()->getData());
            $arrayValues = [];
            foreach ($types as $type) {
                $arrayValues[$type] = $this->getCurrentUserPreferences($type);
            }

            return $arrayValues;
        }
        $key = '';
        if ($this->getUseSession($type)) {
            $sessionName = $this->getSortFilterDisplayValues($type, 'sessionName');
            if ($this->getUseSessionPerPage($type)) {
                $sessionName .= '_' . $this->rootGroup->ID;
            }
            $sessionValue = $this->request->getSession()->get('ProductGroup_' . $sessionName);
            $key = Convert::raw2sql($sessionValue);
        }
        if (! $key) {
            $key = $this->userPreferences[$type] ?? '';
        }
        if (! $key) {
            $key = $this->rootGroup->getListConfigCalculated($type);
        }
        return $this->standardiseCurrentUserPreferences($type, $key);
    }

    public function getGroupFilterTitle(?string $value = ''): string
    {
        return $this->getTitle('GROUPFILTER', $value);
    }

    public function getFilterTitle(?string $value = ''): string
    {
        return $this->getTitle('FILTER', $value);
    }

    public function getSortTitle(?string $value = ''): string
    {
        return $this->getTitle('SORT', $value);
    }

    public function getDisplayTitle(?string $value = ''): string
    {
        return $this->getTitle('DISPLAY', $value);
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
        if ($this->getUseSessionPerPageKey('FILTER') || $this->getCurrentUserPreferencesKey('SORT') || $this->getCurrentUserPreferencesKey('DISPLAY')) {
            $pageId = $this->rootGroup->ID;
        }
        return $this->cacheKey(
            implode(
                '_',
                [
                    serialize($this->request->param('Action')),
                    serialize($this->request->param('ID')),
                    serialize($this->getCurrentUserPreferences('GROUPFILTER')),
                    serialize($this->getCurrentUserPreferencesKey('SORT')),
                    serialize($this->getCurrentUserPreferencesKey('FILTER')),
                    serialize($this->getCurrentUserPreferencesKey('DISPLAY')),
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
                $count = $this->getFinalProductList()->getRawCount();

                if ($count) {
                    $toAdd = $count . ' ' . _t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                    $secondaryTitle .= $this->addToTitle($toAdd);
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->addToTitle($toAdd);
                }
            }

            if ($this->rootGroupController->HasGroupFilter()) {
                $secondaryTitle .= $this->addToTitle($this->rootGroupController->getCurrentFilterTitle());
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
                foreach (['Title', 'MetaTitle', 'MetaDescription'] as $field) {
                    $this->addTitleToField($field, $secondaryTitle);
                }
            }

            // dont update menu title, because the entry in the menu
            // should stay the same as it links back to the unfiltered
            // page (in some cases).

            $this->secondaryTitleHasBeenAdded = true;
        }
    }

    public function standardiseCurrentUserPreferences(string $type, $keyOrArray)
    {
        if (is_array($keyOrArray)) {
            if (isset($keyOrArray['key']) && isset($keyOrArray['params']) && isset($keyOrArray['title'])) {
                return $keyOrArray;
            }
            user_error('Badly set key and params: '.print_r($keyOrArray, 1));
        } else {
            return [
                'key' => $keyOrArray,
                'params' => null,
                'title' => '',
            ];
        }
    }

    public function getOptions(string $classNameOrType): array
    {
        return $this->getTemplateForProductsAndGroups()->getOptions($classNameOrType);
    }

    public function getActions(string $classNameOrType)
    {
        // $answer = [];
        // if ($classNameOrType === 'GROUPFILTER' || $classNameOrType instanceof ProductGroupFilter) {
        //     $groups = $this->getBaseProductList()->getFilterForCandidateCategories();
        //     if ($groups->Count()) {
        //         foreach ($groups as $group) {
        //             $answer[] = $group->FilterForGroupLinkSegment();
        //         }
        //     }
        // }
        // if (! count($answer)) {
        //     $answer = [null];
        // }
        // return $answer;
        if ($classNameOrType === 'GROUPFILTER' || $classNameOrType instanceof ProductGroupFilter) {
            return $this->getBaseProductList()->getFilterForCandidateCategories();
        }
        return null;
    }

    /**
     * full list of options with Links that know about "current"
     * @param  string    $type (GROUPFILTER|FILTER|SORT|DISPLAY)
     * @param  string    $currentKey
     * @param  boolean   $ajaxify
     *
     * @return ArrayList
     */
    public function getLinksPerType(string $type, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        if (! $currentKey) {
            $currentKey = $this->getCurrentUserPreferencesKey($type);
        }
        $options = $this->getOptions($type);
        $actions = $this->getActions($type);
        $optionA = $actions && $options && ($actions->count() * count($options) > 1);
        $optionB = count($options);
        $list = new ArrayList();
        if ($optionA) {
            foreach ($actions as $group) {
                $link = $group->FilterForGroupLinkSegment();
                foreach ($options as $key => $data) {
                    $isCurrent = $currentKey === $key && $this->matchingSegment($link);

                    $obj = new ArrayData(
                        [
                            'Title' => $group->MenuTitle,
                            'Current' => $isCurrent ? true : false,
                            //todo: fix this!!!!
                            'Link' => $this->getLinkTemplate($link, $type, $key),
                            'LinkingMode' => $isCurrent ? 'current' : 'link',
                            'Ajaxify' => $ajaxify,
                            'Object' => $group,
                        ]
                    );
                    $list->push($obj);
                }
            }
        } elseif ($optionB) {
            $link = $this->rootGroup->Link();
            foreach ($options as $key => $data) {
                $isCurrent = $currentKey === $key;
                $obj = new ArrayData(
                    [
                        'Title' => $data['Title'],
                        'Current' => $isCurrent ? true : false,
                        //todo: fix this!!!!
                        'Link' => $this->getLinkTemplate('', $type, $key),
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
        foreach ($this->rootGroupController->getSortFilterDisplayValues() as $myType => $values) {
            if ($type && $myType === $type) {
                if ($replacementForType) {
                    $value = $replacementForType;
                } else {
                    $value = self::GET_VAR_VALUE_PLACE_HOLDER;
                }
            } else {
                $value = $this->getCurrentUserPreferencesKey($myType);
            }
            if ($value !== BaseApplyer::DEFAULT_NAME) {
                $getVars[$values['getVariable']] = $value;
            }
        }
        if (count($getVars)) {
            return $base . '?' . http_build_query($getVars);
        }
        return $base;
    }

    /**
     * TODO: move this to a better place!
     * @param  array $idArray         optional array of IDs to sort by
     * @param  mixed $alternativeSort optional alternative sort
     * @return mixed                  returns null|array|string
     */
    public function setIdArrayDefaultSort(?array $idArray = [], $alternativeSort = null)
    {
        $array = null;
        if ($alternativeSort) {
            $array = $alternativeSort;
        }
        $array = [
            'SORT' => [
                'key' => BaseApplyer::DEFAULT_NAME,
                'params' => $idArray,
                'title' => 'Relevance',
            ],
        ];

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
            ->IsShowFullList($this->getCurrentUserPreferencesKey('DISPLAY')) ? true : false;
    }

    public function getCurrentUserPreferencesKey($type)
    {
        $val = $this->getCurrentUserPreferences($type);
        return $val['key'];
    }

    public function getCurrentUserPreferencesParams($type)
    {
        $val = $this->getCurrentUserPreferences($type);

        return $val['params'];
    }

    public function getCurrentUserPreferencesTitle($type)
    {
        $val = $this->getCurrentUserPreferences($type);

        return $val['title'];
    }

    protected function getFinalProductList()
    {
        return $this->rootGroupController->getFinalProductList();
    }

    protected function getBaseProductList()
    {
        return $this->getFinalProductList()->getBaseProductList();
    }

    protected function getTitle(string $type, ?string $value = ''): string
    {
        $obj = $this->getTemplateForProductsAndGroups()->getApplyer($type);

        return $obj->getTitle($value) . (string) $this->getCurrentUserPreferencesTitle($type);
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

    ###############################
    # segments and actions
    ###############################

    protected function matchingSegment(?string $action): bool
    {
        $outcome = true;
        if ($action) {
            $outcome = $action === $this->mySegment();
        }
        return $outcome;
    }

    protected function mySegment(): string
    {
        return $this->request->param('Action') . '/' . $this->request->param('ID') . '/';
    }
}
