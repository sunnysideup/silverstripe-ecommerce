<?php

namespace Sunnysideup\Ecommerce\Reports;


use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Model\OrderPaymentStatus;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;

trait EcommerceOrderReportTrait
{
    /**
     * not sure if this is used in SS3.
     *
     * @return string
     */
    public function group()
    {
        return _t('EcommerceSideReport.ECOMMERCEGROUP', 'Ecommerce');
    }

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 4500;
    }

    /**
     * working out the items.
     *
     * @param null|mixed $params
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function sourceRecords($params = null, $sort = null, $limit = null)
    {
        Versioned::set_stage(Versioned::DRAFT);
        $className = $this->dataClass;
        $list = $className::get();
        // if ($this->hasMethod('getEcommerceFilter')) {
        //     $filter = $this->getEcommerceFilter();
        //     if (! empty($filter)) {
        //         $list = $list->filter($filter);
        //     }
        // }
        // $sort = null;
        // if ($this->hasMethod('getEcommerceSort')) {
        //     $sort = $this->getEcommerceSort();
        //     if (empty($sort)) {
        //         $sort = ['OrderStatusLogSubmitted.ID' => 'DESC'];
        //     }
        //     if (is_array($sort)) {
        //         $list = $list->sort($sort);
        //     } else {
        //         $list = $list->orderBy($sort);
        //     }
        // }

        // if ($this->hasMethod('getEcommerceWhere')) {
        //     $where = $this->getEcommerceWhere();
        //     if (! empty($where)) {
        //         $list = $list->where($where);
        //     }
        // }

        // if ($this->hasMethod('updateEcommerceList')) {
        //     $list = $this->updateEcommerceList($list);
        // }

        $logs = OrderStatusLogSubmitted::get();

        $startDate = $params['StartDate'] ?? null;
        if (empty($startDate)) {
            $startDate = date('Y-m-d 00:00:00', strtotime('-1 month'));
        }

        if ($startDate) {
            $logs = $logs->filter([
                'Created:GreaterThanOrEqual' => date('Y-m-d 00:00:00', strtotime($startDate))
            ]);
        }

        $endDate = $params['EndDate'] ?? null;
        if ($endDate) {
            $logs = $logs->filter([
                'Created:LessThanOrEqual' => date('Y-m-d 23:59:59', strtotime($endDate))
            ]);
        }

        $isPaid = $params['IsPaid'] ?? null;
        if ('' !== $isPaid) {
            if ('yes' === $isPaid) {
                $orderIds = OrderPaymentStatus::get()
                    ->filter(['IsPaid' => true])
                    ->columnUnique('OrderID');
            } else {
                $orderIds = OrderPaymentStatus::get()
                    ->filter(['IsPaid' => false])
                    ->columnUnique('OrderID');
            }
            $list = $list->filter(['ID' => $orderIds]);
        }

        $list = $list->filter(['ID' => $logs->column('OrderID')]);

        return $list;
    }

    // /**
    //  * @return array
    //  */
    // public function columns() {}

    public function parameterFields()
    {
        $fields = FieldList::create();
        $fields->push(
            FieldGroup::create(
                'Optional Filters',
                DateField::create(
                    'StartDate',
                    'From Date',
                ),
                DateField::create(
                    'EndDate',
                    'Until Date',
                ),
                DropdownField::create(
                    'IsPaid',
                    'Is Paid',
                    [
                        '' => 'All',
                        'yes' => 'Yes',
                        'no' => 'No',
                    ]
                ),
                DropdownField::create(
                    'IsCancelled',
                    'Is Cancelled',
                    [
                        '' => 'All',
                        'yes' => 'Yes',
                        'no' => 'No',
                    ]
                ),
                TextField::create(
                    'CustomerDetails',
                    'Customer Details',

                ),
                TextField::create(
                    'ProductDetails',
                    'Product Details',
                ),
            )->addExtraClass('stacked')
        );
        $fields->recursiveWalk(
            function (FormField $field) {
                if (0 !== strpos($field->getName(), 'filter[')) {
                    $field->setName(sprintf('filters[%s]', $field->getName()));
                }

                $field->addExtraClass('no-change-track'); // ignore in changetracker
            }
        );

        return $fields;
    }
}
