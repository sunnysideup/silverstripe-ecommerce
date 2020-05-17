<?php



/**
 * Selects all products without an image.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports

 **/
class EcommerceSideReportNoImageProducts extends SS_Report
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
        return _t('EcommerceSideReport.NOIMAGE', 'E-commerce: Products without image') .
        ' (' . $this->sourceRecords()->count() . ')';
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
            ->where('"Product"."ImageID" IS NULL OR "Product"."ImageID" <= 0')
            ->sort('FullSiteTreeSort', 'ASC');
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'FullName' => [
                'title' => _t('EcommerceSideReport.BUYABLE_NAME', 'Product'),
                'link' => true,
            ],
        ];
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
