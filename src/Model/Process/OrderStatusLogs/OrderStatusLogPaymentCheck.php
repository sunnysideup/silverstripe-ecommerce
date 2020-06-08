<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\NumericField;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * @Description: We use this payment check class to double check that payment has arrived against
 * the order placed.  We do this independently of Order as a double-check.  It is important
 * that we do this because the main risk in an e-commerce operation is a fake payment.
 * Any e-commerce operator may set up their own policies on what a payment check
 * entails exactly.  It could include a bank reconciliation or even a phone call to the customer.
 * it is important here that we do not add any payment details. Rather, all we have is a tickbox
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
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
    public function canDelete($member = null, $context = [])
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

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.PAYMENTCONFIRMATION', 'Payment Confirmation');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.PAYMENTCONFIRMATIONS', 'Payment Confirmations');
    }

    /**
     * @return SilverStripe\Forms\FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Title');
        $fields->removeByName('Note');
        $fields->addFieldToTab(
            'Root.Main',
            new CheckboxField('PaymentConfirmed', _t('OrderStatusLog.CONFIRMED', 'Payment is confirmed'))
        );

        return $fields;
    }

    /**
     * @return string
     **/
    public function CustomerNote()
    {
        return $this->getCustomerNote();
    }

    public function getCustomerNote()
    {
        if ($this->Author()) {
            Config::nest();
            Config::inst()->update(SSViewer::class, 'theme_enabled', true);
            $html = $this->renderWith('Sunnysideup\Ecommerce\Includes\Order_CustomerNote_PaymentCheck');
            Config::unnest();

            return $html;
        }
    }
}
