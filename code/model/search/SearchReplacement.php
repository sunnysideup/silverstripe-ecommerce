<?php
/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 */
class SearchReplacement extends DataObject implements EditableEcommerceObject
{
    private static $db = array(
        'Search' => 'Varchar(255)',
        'Replace' => 'Varchar(255)',
        'ReplaceWholePhrase' => 'Boolean'
    );

    private static $indexes = array(
        'SearchIndex' => 'unique("Search")',
        'Replace' => true
    );

    private static $summary_fields = array(
        'Search' => 'Aliases (e.g. Biike)',
        'Replace' => 'Proper name (e.g. Bike)',
    );

    private static $field_labels = array(
        'Search' => 'Aliases (e.g. Biike)',
        'Replace' => 'Proper Name (e.g. Bike)',
        'ReplaceWholePhrase' => 'Replace Whole Phrase Only'
    );


    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Search Replacement';
    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Search Replacements';
    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    private static $separator = ',';

    public function fieldLabels($includerelations = true)
    {
        return array(
            'Search' => 'When someone searches for ... (separate searches by '.$this->Config()->get('separator').') - aliases',
            'Replace' => 'It is replaced by - proper name ...',
        );
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //all lower case and make replace double spaces
        $this->Search = trim(preg_replace('!\s+!', ' ', strtolower($this->Search)));
        $searchArray = array();
        foreach (explode(',', $this->Search) as $term) {
            $searchArray[] = trim($term);
        }
        $this->Search = implode(',', $searchArray);
        $this->Replace = strtolower($this->Replace);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canView($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * link to edit the record.
     *
     * @param string | Null $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }
}
