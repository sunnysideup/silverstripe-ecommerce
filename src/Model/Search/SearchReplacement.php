<?php

namespace Sunnysideup\Ecommerce\Model\Search;

use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 *
 * @property string $Search
 * @property string $Replace
 * @property bool $ReplaceWholePhrase
 */
class SearchReplacement extends DataObject implements EditableEcommerceObject
{
    private static $table_name = 'SearchReplacement';

    private static $db = [
        'Search' => 'Varchar(255)',
        'Replace' => 'Varchar(255)',
        'ReplaceWholePhrase' => 'Boolean',
    ];

    private static $indexes = [
        'SearchIndex' => [
            'type' => 'unique',
            'columns' => ['Search'],
        ],
        'Replace' => true,
    ];

    private static $summary_fields = [
        'Search' => 'Search Alias (e.g. nz)',
        'Replace' => 'Actual Search Phrase (e.g. new zealand)',
    ];

    private static $field_labels = [
        'Search' => 'Search Alias (e.g. nz)',
        'Replace' => 'Actual Search Phrase (e.g. new zealand)',
        'ReplaceWholePhrase' => 'The search must match the whole phrase exactly',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Search Replacement';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Search Replacements';

    private static $separator = ',';

    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    public function fieldLabels($includerelations = true)
    {
        return [
            'Search' => 'When someone searches for ... ',
            'Replace' => 'It is replaced by - proper name ...',
        ];
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
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
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * link to edit the record.
     *
     * @param null|string $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->dataFieldByName('Search')
            ->setDescription(
                'e.g. Sonny<br />' .
                    'You can enter more than one search phrase and separate by: ' . $this->Config()->get('separator') . ''
            )
        ;
        $fields->dataFieldByName('Replace')
            ->setDescription(
                'e.g. Sony'
            )
        ;

        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //all lower case and make replace double spaces
        $this->Search = trim(preg_replace('#\s+#', ' ', strtolower((string) $this->Search)));
        $searchArray = [];
        // we make sure that there are no spaces before or after the separator!
        foreach (explode(',', (string) $this->Search) as $term) {
            $searchArray[] = trim($term);
        }
        $this->Search = implode(',', $searchArray);
        $this->Replace = strtolower((string) $this->Replace);
    }
}
