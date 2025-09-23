<?php

namespace Sunnysideup\Ecommerce\Reports;


use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Model\Order;
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
        $list = Order::get();

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
        $ids = ArrayMethods::filter_array($logs->column('OrderID'));
        $list = $list->filter(['ID' => $ids]);

        // filter
        if ($this->hasMethod('getEcommerceFilter')) {
            $filter = $this->getEcommerceFilter();
            if (! empty($filter)) {
                $list = $list->filter($filter);
            }
        }

        // fancy filter
        if ($this->hasMethod('getEcommerceWhere')) {
            $where = $this->getEcommerceWhere();
            if (! empty($where)) {
                $list = $list->where($where);
            }
        }

        //sort
        $sort = null;
        if ($this->hasMethod('getEcommerceSort')) {
            $sort = $this->getEcommerceSort();
            if (empty($sort)) {
                $sort = ['Title' => 'ASC'];
            }
            if (is_array($sort)) {
                $list = $list->sort($sort);
            } else {
                $list = $list->orderBy($sort);
            }
        }

        // final change to update
        if ($this->hasMethod('updateEcommerceList')) {
            $list = $this->updateEcommerceList($list);
        }
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
