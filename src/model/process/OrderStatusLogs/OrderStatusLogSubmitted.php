<?php


/**
 * OrderStatusLogSubmitted is an important class that is created when an order is submitted.
 * It is created by the order and it signifies to the OrderStep to continue to the next step.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
class OrderStatusLogSubmitted extends OrderStatusLog
{
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
        return false;
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
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return true;
    }

    /**
     * can only be created when the order is submitted.
     *
     *@return string
     **/
    public function HTMLRepresentation()
    {
        return $this->getHTMLRepresentation();
    }

    public function getHTMLRepresentation()
    {
        if ($this->OrderAsHTML) {
            return $this->OrderAsHTML;
        } elseif ($this->OrderAsString) {
            return unserialize($this->OrderAsString);
        }

        return _t('OrderStatusLog.NO_FURTHER_INFO_AVAILABLE', 'no further information available');
    }

    /**
     * adding a sequential order number.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($order = $this->Order()) {
            if (! $this->Total) {
                $this->Total = $order->Total();
                $this->SubTotal = $order->SubTotal();
            }
        }
        if (! intval($this->SequentialOrderNumber)) {
            $this->SequentialOrderNumber = 1;
            $min = intval(EcommerceConfig::get('Order', 'order_id_start_number')) - 0;
            if (isset($this->ID)) {
                $id = intval($this->ID);
            } else {
                $id = 0;
            }
            $lastOne = DataObject::get_one(
                'OrderStatusLogSubmitted',
                '\'ID\' != \'' . $id . '\'',
                $cacheDataObjectGetOne = true,
                ['SequentialOrderNumber' => 'DESC']
            );
            if ($lastOne) {
                $this->SequentialOrderNumber = intval($lastOne->SequentialOrderNumber) + 1;
            }
            if (intval($min) && $this->SequentialOrderNumber < $min) {
                $this->SequentialOrderNumber = $min;
            }
        }
    }
}

