<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Api\SetThemed;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogDispatchPhysicalOrder
 *
 * @property string $DispatchedBy
 * @property string $DispatchedOn
 * @property string $DispatchTicket
 * @property string $DispatchLink
 * @property bool $Sent
 */
class OrderStatusLogDispatchPhysicalOrder extends OrderStatusLogDispatch
{
    private static $table_name = 'OrderStatusLogDispatchPhysicalOrder';

    private static $db = [
        'DispatchedBy' => 'Varchar(100)',
        'DispatchedOn' => 'Date',
        'DispatchTicket' => 'Varchar(100)',
        'DispatchLink' => 'Varchar(255)',
        'Sent' => 'Boolean',
        'BypassSendingGoods' => 'Boolean',
    ];

    private static $indexes = [
        'DispatchedOn' => true,
        'DispatchTicket' => true,
    ];

    private static $searchable_fields = [
        'OrderID' => [
            'field' => NumericField::class,
            'title' => 'Order Number',
        ],
        'Title' => 'PartialMatchFilter',
        'Note' => 'PartialMatchFilter',
        'DispatchedBy' => 'PartialMatchFilter',
        'DispatchTicket' => 'PartialMatchFilter',
    ];

    private static $summary_fields = [
        'DispatchedOn' => 'Date',
        'DispatchedBy' => 'Dispatched By',
        'OrderID' => 'Order ID',
    ];

    private static $defaults = [
        'InternalUseOnly' => false,
    ];

    private static $singular_name = 'Order Log Physical Dispatch Entry';

    private static $plural_name = 'Order Log Physical Dispatch Entries';

    private static $default_sort = [
        'DispatchedOn' => 'DESC',
        'ID' => 'DESC',
    ];

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ORDERLOGPHYSICALDISPATCHENTRY', 'Order Log Physical Dispatch Entry');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.ORDERLOGPHYSICALDISPATCHENTRIES', 'Order Log Physical Dispatch Entries');
    }

    public function populateDefaults()
    {
        $this->Title = _t('OrderStatusLog.ORDERDISPATCHED', 'Order Dispatched');
        $this->DispatchedOn = date('Y-m-d');
        if (Security::database_is_ready()) {
            if (Security::getCurrentUser()) {
                $this->DispatchedBy = Security::getCurrentUser()->getTitle();
            }
        }

        return parent::populateDefaults();
    }

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $dispatchedOnLabel = _t('OrderStatusLog.DISPATCHEDON', 'Dispatched on');
        $fields->replaceField('DispatchedOn', $dispatchedOnField = new TextField('DispatchedOn', $dispatchedOnLabel));
        $dispatchedOnField->setDescription(_t('OrderStatusLog.DISPATCHED_ON_NOTE', 'Please use year-month-date, e.g. 2015-11-23'));
        $dispatchLinkField = $fields->dataFieldByName('DispatchLink');
        $dispatchLinkField->setDescription(_t('OrderStatusLog.LINK_EXAMPLE', 'e.g. http://www.ups.com/mytrackingnumber'));
        $dispatchLinkField = $fields->dataFieldByName('Note');
        $dispatchLinkField->setTitle(_t('OrderStatusLog.NOTE_NEW_TITLE', 'Customer Message (*)'));
        $dispatchLinkField->setDescription(_t('OrderStatusLog.NOTE_NOTE', 'This field is required'));

        return $fields;
    }

    /**
     * @return string
     */
    public function CustomerNote()
    {
        return $this->getCustomerNote();
    }

    public function getCustomerNote()
    {
        SetThemed::start();
        $html = $this->renderWith('Sunnysideup\Ecommerce\Includes\LogDispatchPhysicalOrderCustomerNote');
        SetThemed::end();

        return $html;
    }

    public function getFrontEndFields($params = null)
    {
        $order = $this->getOrderCached();
        if($order) {
            $fields = FieldList::create(
                [
                    ReadonlyField::create('CustomerInfo', 'Customer', $order->Member()->getCustomerDetails()),
                    ReadonlyField::create('OrderInfo', 'Order', $order->getTitle()),
                    ReadonlyField::create('OrderItemInfo', 'Items', $this->renderWith('Sunnysideup\\Ecommerce\\Includes\\OrderItemsTiny')),
                    TextField::create('DispatchedBy'),
                    TextField::create('DispatchTicket'),
                    TextField::create('DispatchLink'),
                    CheckboxField::create('Sent'),
                    CheckboxField::create('BypassSendingGoods'),
                    CheckboxField::create('BypassEmailing'),
                    CheckboxField::create('InternalUseOnly', 'Do not send update to customer'),
                ]
            );
        } else {
            $fields = FieldList::create(
                [
                    LiteralField::create('OrderNotFound', '<p class="message warning">Order not found.</p>'),
                ]
            );
        }
        $this->updateFrontEndFields($fields);
        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->DispatchedOn) {
            $this->DispatchedOn = DBField::create_field(DBDate::class, date('Y-m-d'));
        }
    }
}
