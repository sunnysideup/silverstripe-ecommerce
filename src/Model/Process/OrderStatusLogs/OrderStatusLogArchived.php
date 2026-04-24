<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use Override;
use SilverStripe\Security\Member;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogArchived
 */
class OrderStatusLogArchived extends OrderStatusLog
{
    private static $table_name = 'OrderStatusLogArchived';

    private static $defaults = [
        'InternalUseOnly' => false,
    ];

    private static $singular_name = 'Archived Order - Additional Note';

    private static $plural_name = 'Archived Order - Additional Notes';

    #[Override]
    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ARCHIVEDORDERS', 'Archived Order - Additional Note');
    }

    #[Override]
    public function plural_name()
    {
        return _t('OrderStatusLog.ARCHIVEDORDERS', 'Archived Order - Additional Notes');
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    #[Override]
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }

        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
    public function canCreate($member = null, $context = [])
    {
        return true;
    }

    /**
     * @return FieldList
     */
    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('ClassName', HiddenField::create('ClassName', 'ClassName', $this->ClassName));
        $fields->addFieldToTab('Root.Main', ReadonlyField::create('Created', 'Created'));

        return $fields;
    }
}
