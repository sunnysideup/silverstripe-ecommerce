<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * OrderStatusLogSubmitted is an important class that is created when an order is submitted.
 *
 * It is created by the order and it signifies to the OrderStep to continue to the next step.
 *
 * @property string $OrderAsHTML
 * @property string $OrderAsString
 * @property int $SequentialOrderNumber
 * @property float $Total
 * @property float $SubTotal
 */
class OrderStatusLogSubmitted extends OrderStatusLog
{
    private static $table_name = 'OrderStatusLogSubmitted';

    private static $db = [
        'OrderAsHTML' => 'HTMLText',
        'OrderAsString' => 'Text',
        'SequentialOrderNumber' => 'Int',
        'Total' => 'Currency',
        'SubTotal' => 'Currency',
    ];

    private static $defaults = [
        'InternalUseOnly' => true,
    ];

    private static $indexes = [
        'SequentialOrderNumber' => true,
    ];

    private static $casting = [
        'HTMLRepresentation' => 'HTMLText',
    ];

    private static $singular_name = 'Submitted Order';

    private static $plural_name = 'Submitted Orders';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'The record that the order has been submitted by the customer.  This is important in e-commerce, because from here, nothing can change to the order.';

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.SUBMITTEDORDER', 'Submitted Order - Fulltext Backup');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.SUBMITTEDORDERS', 'Submitted Orders - Fulltext Backup');
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

        return true;
    }

    /**
     * can only be created when the order is submitted.
     *
     * @return string
     */
    public function HTMLRepresentation()
    {
        return $this->getHTMLRepresentation();
    }

    public function getHTMLRepresentation()
    {
        if ($this->OrderAsHTML) {
            return $this->OrderAsHTML;
        }
        if ($this->OrderAsString) {
            return unserialize((string) $this->OrderAsString);
        }

        return _t('OrderStatusLog.NO_FURTHER_INFO_AVAILABLE', 'no further information available');
    }

    /**
     * adding a sequential order number.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $order = $this->getOrderCached();
        // todo: this needs work!
        // $this->OrderAsString = $order->ConvertToString();
        if ($order instanceof \Sunnysideup\Ecommerce\Model\Order && ! $this->Total) {
            $this->Total = $order->Total();
            $this->SubTotal = $order->SubTotal();
        }
        if ((int) $this->SequentialOrderNumber === 0) {
            $min = (int) EcommerceConfig::get(Order::class, 'order_id_start_number') ?? 1;
            $id = null !== $this->ID ? (int) $this->ID : 0;
            $lastOne = DB::query("SELECT MAX(SequentialOrderNumber) AS LastOrderNumber FROM OrderStatusLogSubmitted WHERE ID <> $id")->value();
            if ($lastOne > 0) {
                $this->SequentialOrderNumber = (int) $lastOne + 1;
            }
            if ($min && $this->SequentialOrderNumber < $min) {
                $this->SequentialOrderNumber = $min;
            }
        }
        // important to reload submission log!
        $order->IsSubmitted($recalculate = true);

        // order does not need to be recalculated anymore!
        Order::set_needs_recalculating(false, $order->ID);
    }
}
