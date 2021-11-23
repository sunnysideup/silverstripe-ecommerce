<?php

namespace Sunnysideup\Ecommerce\Reports;

use Sunnysideup\Ecommerce\Pages\Product;

use SilverStripe\Forms\GridField\GridFieldExportButton;

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
        if($this->hasMethod('getEcommerceFilter')) {
            $filter = $this->getEcommerceFilter();
            if(!empty($filter)) {
                $list = $list->filter($filter);
            }
        }
        if($this->hasMethod('getEcommerceSort')) {
            $sort = $this->getEcommerceSort();
            if(!empty($sort)) {
                $list = $list->sort($sort);
            }
        }
        if($this->hasMethod('getEcommerceWhere')) {
            $where = $this->getEcommerceWhere();
            if(!empty($where)) {
                $list = $list->where($where);
            }
        }
        if($this->hasMethod('updateEcommerceList')) {
            $list = $this->updateEcommerceList($list);
        }
        return $list;
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'InternalItemID' => 'Product Code',
            'ProductType' => 'Product Type',
            'FullName' => [
                'title' => _t('EcommerceSideReport.BUYABLE_NAME', 'Item'),
                'link' => true,
            ],
        ];
    }

}
