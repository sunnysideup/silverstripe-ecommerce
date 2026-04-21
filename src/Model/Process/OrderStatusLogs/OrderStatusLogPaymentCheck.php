<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use Override;
use SilverStripe\Security\Member;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\NumericField;
use Sunnysideup\Ecommerce\Api\SetThemed;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogPaymentCheck
 *
 * @property bool $PaymentConfirmed
 */
class OrderStatusLogPaymentCheck extends OrderStatusLog
{
    private static $table_name = 'OrderStatusLogPaymentCheck';

    private static $defaults = [
        'InternalUseOnly' => true,
    ];

    private static $db = [
        'PaymentConfirmed' => 'Boolean',
    ];

    private static $searchable_fields = [
        'OrderID' => [
            'field' => NumericField::class,
            'title' => 'Order Number',
        ],
        'PaymentConfirmed' => true,
    ];

    private static $summary_fields = [
        'Created' => 'Date',
        'Author.Title' => 'Checked by',
        'PaymentConfirmedNice' => 'Payment Confirmed',
    ];

    private static $casting = [
        'PaymentConfirmedNice' => 'Varchar',
    ];

    private static $singular_name = 'Payment Confirmation';

    private static $plural_name = 'Payment Confirmations';

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

    public function PaymentConfirmedNice()
    {
        return $this->getPaymentConfirmedNice();
    }

    public function getPaymentConfirmedNice()
    {
        if ($this->PaymentConfirmed) {
            return _t('OrderStatusLog.YES', 'yes');
        }

        return _t('OrderStatusLog.No', 'no');
    }

    #[Override]
    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.PAYMENTCONFIRMATION', 'Payment Confirmation');
    }

    #[Override]
    public function plural_name()
    {
        return _t('OrderStatusLog.PAYMENTCONFIRMATIONS', 'Payment Confirmations');
    }

    /**
     * @return FieldList
     */
    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Title');
        $fields->removeByName('Note');
        $fields->addFieldToTab(
            'Root.Main',
            CheckboxField::create('PaymentConfirmed', _t('OrderStatusLog.CONFIRMED', 'Payment is confirmed'))
        );

        return $fields;
    }

    /**
     * @return string
     */
    #[Override]
    public function CustomerNote()
    {
        return $this->getCustomerNote();
    }

    #[Override]
    public function getCustomerNote()
    {
        if ($this->Author()) {
            SetThemed::start();
            $html = $this->renderWith('Sunnysideup\Ecommerce\Includes\Order_CustomerNote_PaymentCheck');
            SetThemed::end();

            return $html;
        }

        return null;
    }
}
