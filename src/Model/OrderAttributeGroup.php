<?php

namespace Sunnysideup\Ecommerce\Model;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * Allows you to group OrderAttributes.
 */
class OrderAttributeGroup extends DataObject implements EditableEcommerceObject
{
    private static $table_name = 'OrderAttributeGroup';

    private static $db = [
        'Name' => 'Varchar',
        'Sort' => 'Int',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $indexes = [
        'Sort' => true,
    ];

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Order Modifier Group';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Order Modifier Types';

    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canDelete($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
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
     * @param string | Null $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }
}
