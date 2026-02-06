<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogCancel
 */
class OrderStatusLogCancel extends OrderStatusLog
{
    private static $defaults = [
        'Title' => 'Order Cancelled',
        'InternalUseOnly' => false,
    ];

    private static $singular_name = 'Cancelled Order';

    private static $plural_name = 'Cancelled Orders';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A record noting the cancellation of an order.  ';

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.SUBMITTEDORDER', 'Cancelled Order');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.SUBMITTEDORDERS', 'Cancelled Orders');
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
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

        return false;
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
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

        return false;
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
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

        return false;
    }
}
