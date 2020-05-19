<?php

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
class OrderStatusLogArchived extends OrderStatusLog
{
    private static $defaults = [
        'InternalUseOnly' => false,
    ];

    private static $singular_name = 'Archived Order - Additional Note';

    private static $plural_name = 'Archived Order - Additional Notes';

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ARCHIVEDORDERS', 'Archived Order - Additional Note');
    }

    public function i18n_plural_name()
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
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * Standard SS method.
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

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
    {
        return true;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('ClassName', new HiddenField('ClassName', 'ClassName', $this->ClassName));
        $fields->addFieldToTab('Root.Main', new ReadonlyField('Created', 'Created'));

        return $fields;
    }
}

