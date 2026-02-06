<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\TextField;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;
use Sunnysideup\Ecommerce\Pages\Product;

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

        $product = $params['ProductID'] ?? null;
        if ($product) {
            $productID = (int) $product;
            $product = Product::get()->byID($productID);
            if ($product) {
                $orderItems = OrderItem::get()->filter(['BuyableID' => $product->ID]);
                $productOrderIDs = $orderItems->columnUnique('OrderID');
                $logs = $logs->filter(['OrderID' => $productOrderIDs]);
            }
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
            $list = is_array($sort) ? $list->sort($sort) : $list->orderBy($sort);
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
                DropdownField::create(
                    'ProductID',
                    'Product',
                    Product::get()->map('ID', 'FullName')->toArray()
                )->setEmptyString('-- Any Product --'),
            )->addExtraClass('stacked'),
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

    public function getReportField()
    {
        $field = parent::getReportField();
        $config = $field->getConfig();
        $config->getComponentByType(GridFieldExportButton::class)
            ->setExportColumns($this->getExportFields());
        return $field;
    }

    public function getExportFields(): array
    {
        $v = Config::inst()->get(Order::class, 'csv_export_fields');
        if (is_array($v)) {
            return $v;
        }
        return Config::inst()->get(Order::class, 'summary_fields');
    }
}
