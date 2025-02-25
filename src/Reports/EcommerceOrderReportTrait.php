<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

trait EcommerceProductGroupReportTrait
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
        /**
         * @var
         * SELECT BillingAddress.PostalCode, COUNT(Order.ID) AS OrderCount, COUNT(`Order`.ID) AS TotalSalesCount
         * FROM `Order`
         * INNER JOIN BillingAddress ON `Order`.BillingAddressID = BillingAddress.ID
         * WHERE  `Order`.Created >= '2024-01-01'
         * GROUP BY BillingAddress.PostalCode
         * ORDER BY TotalSalesCount DESC;
         */
        $className = Order::class;
        $list = $className::get();
        if ($this->hasMethod('getEcommerceFilter')) {
            $filter = $this->getEcommerceFilter();
            if (! empty($filter)) {
                $list = $list->filter($filter);
            }
        }
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

        if ($this->hasMethod('getEcommerceWhere')) {
            $where = $this->getEcommerceWhere();
            if (! empty($where)) {
                $list = $list->where($where);
            }
        }

        if ($this->hasMethod('updateEcommerceList')) {
            $list = $this->updateEcommerceList($list);
        }

        $title = (string) Convert::raw2sql($params['Title'] ?? '');
        if ($title) {
            $list = $list->filterAny(['Title:PartialMatch' => $title, 'AlternativeProductGroupNames:PartialMatch' => $title]);
        }

        $createdInTheLastXDays = (int) ($params['CreatedInTheLastXDays'] ?? 0);
        if ($createdInTheLastXDays) {
            $list = $list->where(['"Created" >= DATE_ADD(CURDATE(), INTERVAL -' . (int) $createdInTheLastXDays . ' DAY)']);
        }

        return $list;
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'City' => [
                'title' => _t('EcommerceSideReport.CITY', 'City'),
                'link' => true,
            ],
        ];
    }

    public function parameterFields()
    {
        $fields = FieldList::create();
        $productTypes = $this->getProductGroupTypes();
        $fields->push(
            FieldGroup::create(
                'Optional Filters',
                TextField::create(
                    'Title',
                    'Keyword',
                ),
                NumericField::create(
                    'CreatedInTheLastXDays',
                    'Created less than ... days ago?',
                    ''
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
