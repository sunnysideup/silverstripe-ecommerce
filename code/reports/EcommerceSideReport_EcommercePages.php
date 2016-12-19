<?php
/**
 * EcommerceSideReport classes are to allow quick reports that can be accessed
 * on the Reports tab to the left inside the SilverStripe CMS.
 * Currently there are reports to show products flagged as 'FeatuedProduct',
 * as well as a report on all products within the system.
 */

/**
 * Ecommerce Pages except Products.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceSideReport_EcommercePages extends SS_Report
{
    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'SiteTree';

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.ECOMMERCEPAGES', 'E-commerce: Non-product e-commerce pages').
        ' ('.$this->sourceRecords()->count().')';
    }

    private static $additional_classnames = array();

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
        return SiteTree::get()->filter('ClassName', array('CartPage', 'AccountPage', 'ProductGroupSearchPage', 'CheckoutPage', 'OrderConfirmationPage') + (array) $this->Config()->get('additional_classnames'));
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
