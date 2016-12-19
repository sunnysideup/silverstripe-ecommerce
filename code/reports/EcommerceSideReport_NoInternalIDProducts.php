<?php


/**
 * Selects all products without an InternalID.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceSideReport_NoInternalIDProducts extends SS_Report
{
    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'Product';

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOINTERNALID', 'E-commerce: Products without Internal ID / SKU ').
        ' ('.$this->sourceRecords()->count().')';
    }

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
     * @return DataList
     */
    public function sourceRecords($params = null)
    {
        return Product::get()
            ->where("\"Product\".\"InternalItemID\" IS NULL OR \"Product\".\"InternalItemID\" = '' ")
            ->sort('FullSiteTreeSort', 'ASC');
    }

    /**
     * @return array
     */
    public function columns()
    {
        return array(
            'FullName' => array(
                'title' => _t('EcommerceSideReport.BUYABLE_NAME', 'Product'),
                'link' => true,
            ),
        );
    }


    public function getReportField()
    {
        $field = parent::getReportField();
        $config = $field->getConfig();
        $exportButton = $config->getComponentByType('GridFieldExportButton');
        $exportButton->setExportColumns($field->getColumns());
        
        return $field;
    }
}
