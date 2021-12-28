<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;

trait EcommerceProductReportTrait
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
        return 7000;
    }

    /**
     * working out the items.
     *
     * @param null|mixed $params
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function sourceRecords($params = null)
    {
        $className = $this->dataClass;
        $list = $className::get();
        if ($this->hasMethod('getEcommerceFilter')) {
            $filter = $this->getEcommerceFilter();
            if (! empty($filter)) {
                $list = $list->filter($filter);
            }
        }
        if ($this->hasMethod('getEcommerceSort')) {
            $sort = $this->getEcommerceSort();
            $list = empty($sort) ? $list->sort(['FullSiteTreeSort' => 'ASC']) : $list->sort($sort);
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
        $minPrice = (float) ($params['MinimumPrice'] ?? 0);
        if ($minPrice) {
            $list = $list->filter(['Price:GreaterThan' => $minPrice]);
        }
        $forSale = $params['ForSale'] ?? '';
        if ($forSale) {
            if ('Yes' === $forSale) {
                $filter = 1;
            } elseif ('No') {
                $filter = 0;
            }
            $list = $list->filter(['AllowPurchase' => $filter]);
        }
        $changedInTheLastXDays = (int) ($params['ChangedInTheLastXDays'] ?? 0);
        if ($changedInTheLastXDays) {
            $list = $list->where(['"LastEdited" >= DATE_ADD(CURDATE(), INTERVAL -' . (int) $changedInTheLastXDays . ' DAY)']);
        }

        return $list;
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'InternalItemID' => [
                'title' => _t('EcommerceSideReport.PRODUCT_TYPE', 'Product Code'),
                'link' => true,
            ],
            'ProductType' => [
                'title' => _t('EcommerceSideReport.PRODUCT_TYPE', 'Product Type'),
                'link' => true,
            ],
            'FullName' => [
                'title' => _t('EcommerceSideReport.BUYABLE_NAME', 'Item'),
                'link' => true,
            ],
        ];
    }

    public function parameterFields()
    {
        $params = FieldList::create();

        $params->push(
            CurrencyField::create(
                'MinimumNPrice',
                'Minimum Price',
                0
            )
        );
        $params->push(
            DropdownField::create(
                'ForSale',
                'For Sale',
                [
                    'Yes' => 'Yes',
                    'No' => 'No',
                ]
            )
                ->setEmptyString('--- Any ---')
        );

        $params->push(
            NumericField::create(
                'ChangedInTheLastXDays',
                'Changed less than ... days ago?',
                ''
            )
        );

        return $params;
    }

    // public function getReportField()
    // {
    //     $field = parent::getReportField();
    //     $config = $field->getConfig();
    //     $exportButton = $config->getComponentByType(GridFieldExportButton::class);
    //     $exportButton->setExportColumns($field->getColumns());
    //
    //     return $field;
    // }
}
