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
use Sunnysideup\Ecommerce\ProductsAndGroups\ProductGroupSchema;
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

    /**
     * @var string
     */
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
     * @var array
     */
    protected $useSession = [
        'FILTER' => false,
        'SORT' => false,
        'DISPLAY' => false,
    ];

    /**
     * @var array
     */
    protected $useSessionPerPage = [
        'FILTER' => false,
        'SORT' => false,
        'DISPLAY' => false,
    ];

    /**
     * @var HTTPRequest
     */
    protected $request;

    /**
     * @var ContentController
     */
    protected $rootGroupController;

    protected $rootGroup;

    /**
     * here is where we save the GET variables and any other settings for FILTER|SORT|DISPLAY.
     *
     * @var array
     */
    protected $userPreferences = [];

    /**
     * keep a store for every page setting?
     * For example, do we store in session how a particular page is filtered / sorted.
     *
     * @var array
     */
    private static $use_session_per_page = [];

    /**
     * keep a store for every FILTER|SORT|DISPLAY setting?
     *
     * @var arrayn setUser
     */
    private static $use_session = [];

    /**
     * @param bool $useSession
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
        $types = array_keys($this->getProductGroupSchema()->getData());
        foreach ($types as $type) {
            $array[$type] = $this->getUseSession($type);
        }

        return $array;
    }

    /**
     * @param bool $useSessionPerPage
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
        $types = array_keys($this->getProductGroupSchema()->getData());
        foreach ($types as $type) {
            $array[$type] = $this->getUseSessionPerPage($type);
        }

        return $array;
    }

    /**
     * @param HTTPRequest $request
     */
    public function setRequest($request): self
    {
        ClassHelpers::check_for_instance_of($request, HTTPRequest::class, true);
        $this->request = $request;

        return $this;
    }

    /**
     * @param ProductGroupController $rootGroupController
     */
    public function setRootGroupController($rootGroupController): self
    {
        ClassHelpers::check_for_instance_of($rootGroupController, ProductGroupController::class, true);
        $this->rootGroupController = $rootGroupController;

        return $this;
    }

    /**
     * @param ProductGroup $rootGroup
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
     *                             an array can be like this:
     *                             ```php
     *                             FILTER => 'abc'
     *                             ```
     *                             OR
     *                             ```php
     *                             FILTER => ['key' => 'foo', 'params' => 'bar', 'title' => 'foo bar']
     *                             ```
     */
    public function saveUserPreferences(?array $overrideArray = []): self
    {
        $sortFilterDisplayNames = $this->rootGroupController->getSortFilterDisplayValues();
        $isSearch = false;
        foreach ($sortFilterDisplayNames as $type => $oneTypeArray) {
            $getVariableName = $oneTypeArray['getVariable'];
            if (isset($overrideArray[$type])) {
                $newPreference = $overrideArray[$type];
            } elseif (! isset($this->userPreferences[$type])) {
                $newPreference = $this->request->getVar($getVariableName);
                if ($type === 'GROUPFILTER') {
                    if ($newPreference) {
                        $otherProductGroup = ProductGroupFilter::get_group_from_get_variable($newPreference);
                        if ($otherProductGroup) {
                            $newPreference = [
                                'key' => 'default',
                                'params' => $otherProductGroup->FilterForGroupSegment(),
                                'title' => $otherProductGroup->MenuTitle,
                            ];
                        }
                    }
                }
                if ($type === 'SEARCHFILTER') {
                    if (1 === (int) $newPreference) {
                        $isSearch = true;
                        $newPreference = [
                            'key' => 'default',
                            'params' => $this->request->getVars(),
                            'title' => 'Search Results',
                        ];
                    }
                }
            } else {
                $newPreference = $this->userPreferences[$type];
            }
            $this->userPreferences[$type] = $newPreference;
            //save preference to session
            $this->savePreferenceToSession($type, $newPreference);
        }
        // irrespective of preference for page, use default for SORT in case of SEARCH.
        if ($isSearch && ! $this->userPreferences['SORT']) {
            $this->userPreferences['SORT'] = BaseApplyer::DEFAULT_NAME;
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
    public function getCurrentUserPreferences(?string $type = '')
    {
        if (! $type) {
            $types = array_keys($this->getProductGroupSchema()->getData());
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

    public function getSearchFilterTitle(?string $value = ''): string
    {
        return $this->getTitle('SEARCHFILTER', $value);
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
     */
    public function ProductGroupListCachingKey(?bool $withPageNumber = false): string
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
                    serialize($this->getCurrentUserPreferencesKey('SEARCHFILTER')),
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
            if (isset($keyOrArray['key'], $keyOrArray['params'], $keyOrArray['title'])) {
                return $keyOrArray;
            }
            user_error('Badly set key and params: ' . print_r($keyOrArray, 1));
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
        return $this->getProductGroupSchema()->getOptions($classNameOrType);
    }

    public function getActions(string $classNameOrType)
    {
        if ('GROUPFILTER' === $classNameOrType || $classNameOrType instanceof ProductGroupFilter) {
            return $this->getBaseProductList()->getFilterForCandidateCategories();
        }

        return null;
    }

    /**
     * full list of options with Links that know about "current".
     *
     * @param string $type       (SEARCHFILTER|GROUPFILTER|FILTER|SORT|DISPLAY)
     * @param string $currentKey
     * @param bool   $ajaxify
     */
    public function getLinksPerType(string $type, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        $options = $this->getOptions($type);
        $actions = $this->getActions($type);
        $optionA = $actions && $options && ($actions->count() * count($options) > 1);
        $optionB = count($options);
        $list = new ArrayList();
        if ($optionA) {
            if (! $currentKey) {
                $currentKey = $this->getCurrentUserPreferencesParams($type);
            }
            foreach ($actions as $group) {
                foreach (array_keys($options) as $key) {
                    $isCurrent = $currentKey === $group->FilterForGroupSegment();
                    $obj = new ArrayData(
                        [
                            'Title' => $group->MenuTitle,
                            'Current' => $isCurrent,
                            //todo: fix this!!!!
                            'Link' => $this->getLinkTemplate('', $type, $group->FilterForGroupSegment()),
                            'LinkingMode' => $isCurrent ? 'current' : 'link',
                            'Ajaxify' => $ajaxify,
                            'Object' => $group,
                        ]
                    );
                    $list->push($obj);
                }
            }
        } elseif ($optionB) {
            if (! $currentKey) {
                $currentKey = $this->getCurrentUserPreferencesKey($type);
            }
            foreach ($options as $key => $data) {
                $isCurrent = $currentKey === $key;
                $obj = new ArrayData(
                    [
                        'Title' => $data['Title'],
                        'Current' => $isCurrent,
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
     * instead of the value for that type, we add: '[[INSERT_HERE]]'.
     *
     * @param string $action             optional
     * @param string $type               optional
     * @param string $replacementForType optional - what you would like the type be instead! - e.g. for FILTER I'd like it to be "somethingelse"
     */
    public function getLinkTemplate(?string $action = null, ?string $type = '', ?string $replacementForType = ''): string
    {
        $base = $this->rootGroup->Link($action);
        $getVars = [];
        foreach ($this->rootGroupController->getSortFilterDisplayValues() as $myType => $values) {
            if ($type && $myType === $type) {
                $value = $replacementForType ? $replacementForType : self::GET_VAR_VALUE_PLACE_HOLDER;
            } else {
                $value = $this->getCurrentUserPreferencesKey($myType);
            }
            if (BaseApplyer::DEFAULT_NAME !== $value) {
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
     *
     * @param array $idArray         optional array of IDs to sort by
     * @param mixed $alternativeSort optional alternative sort
     *
     * @return mixed returns null|array|string
     */
    public function setIdArrayDefaultSort(?array $idArray = [], $alternativeSort = null)
    {
        if ($alternativeSort) {
            $array = $alternativeSort;
        } else {
            $array = [
                'SORT' => [
                    'key' => BaseApplyer::DEFAULT_NAME,
                    'params' => $idArray,
                    'title' => 'Relevance',
                ],
            ];
        }
        if ($array) {
            $this->saveUserPreferences($array);
        }

        return $array;
    }

    /**
     * special case of full list.
     */
    public function IsShowFullList(): bool
    {
        return $this->getProductGroupSchema()
            ->getApplyer('DISPLAY')
            ->IsShowFullList($this->getCurrentUserPreferencesKey('DISPLAY'))
        ;
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

    public function getTitle(string $type, ?string $value = ''): string
    {
        $obj = $this->getProductGroupSchema()->getApplyer($type);

        return $obj->getTitle($value) . $this->getCurrentUserPreferencesTitle($type);
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
        return $this->rootGroupController->getRequest()->getSession();
    }

    protected function getFinalProductList()
    {
        return $this->rootGroupController->getFinalProductList();
    }

    protected function getBaseProductList()
    {
        return $this->getFinalProductList()->getBaseProductList();
    }

    /**
     * removes any spaces from the 'toAdd' bit and adds the pipe if there is
     * anything to add at all.  Through the lang files, you can change the pipe
     * symbol to anything you like.
     *
     * @param string $toAdd the string to add
     *
     * @return string the string to add, cleaned up and with prefix and so on added
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

    protected function addTitleToField(string $field, string $secondaryTitle): self
    {
        if (! empty($this->rootGroupController->{$field})) {
            $this->rootGroupController->{$field} .= $secondaryTitle;
        }

        return $this;
    }

    /**
     * @return ProductGroupSchema
     */
    protected function getProductGroupSchema()
    {
        $obj = $this->rootGroup->getProductGroupSchema();
        ClassHelpers::check_for_instance_of($obj, ProductGroupSchema::class, true);

        return $obj;
    }

    //##############################
    // segments and actions
    //##############################

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
