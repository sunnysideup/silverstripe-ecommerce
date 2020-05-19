<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use Security;
use Member;
use TextField;
use DBField;
use Config;



/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
class OrderStatusLogDispatchPhysicalOrder extends OrderStatusLogDispatch
{
    private static $db = [
        'DispatchedBy' => 'Varchar(100)',
        'DispatchedOn' => 'Date',
        'DispatchTicket' => 'Varchar(100)',
        'DispatchLink' => 'Varchar(255)',
    ];

    private static $indexes = [
        'DispatchedOn' => true,
        'DispatchTicket' => true,
    ];

    private static $searchable_fields = [
        'OrderID' => [
            'field' => 'NumericField',
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
        parent::populateDefaults();
        $this->Title = _t('OrderStatusLog.ORDERDISPATCHED', 'Order Dispatched');
        $this->DispatchedOn = date('Y-m-d');
        if (Security::database_is_ready()) {
            if (Member::currentUser()) {
                $this->DispatchedBy = Member::currentUser()->getTitle();
            }
        }
    }

    /**
     *@return FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $dispatchedOnLabel = _t('OrderStatusLog.DISPATCHEDON', 'Dispatched on');
        $fields->replaceField('DispatchedOn', $dispatchedOnField = new TextField('DispatchedOn', $dispatchedOnLabel));
        $dispatchedOnField->setRightTitle(_t('OrderStatusLog.DISPATCHED_ON_NOTE', 'Please use year-month-date, e.g. 2015-11-23'));
        $dispatchLinkField = $fields->dataFieldByName('DispatchLink');
        $dispatchLinkField->setRightTitle(_t('OrderStatusLog.LINK_EXAMPLE', 'e.g. http://www.ups.com/mytrackingnumber'));
        $dispatchLinkField = $fields->dataFieldByName('Note');
        $dispatchLinkField->setTitle(_t('OrderStatusLog.NOTE_NEW_TITLE', 'Customer Message (*)'));
        $dispatchLinkField->setRightTitle(_t('OrderStatusLog.NOTE_NOTE', 'This field is required'));
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (! $this->DispatchedOn) {
            $this->DispatchedOn = DBField::create_field('Date', date('Y-m-d'));
        }
    }

    /**
     *@return string
     **/
    public function CustomerNote()
    {
        return $this->getCustomerNote();
    }

    public function getCustomerNote()
    {
        Config::nest();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        $html = $this->renderWith('LogDispatchPhysicalOrderCustomerNote');
        Config::unnest();

        return $html;
    }
}

