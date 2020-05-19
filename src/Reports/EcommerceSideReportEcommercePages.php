<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\AccountPage;
use Sunnysideup\Ecommerce\Pages\CartPage;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;
use Sunnysideup\Ecommerce\Pages\OrderConfirmationPage;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;

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

 **/
class EcommerceSideReportEcommercePages extends Report
{
    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = SiteTree::class;

    private static $additional_classnames = [];

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.ECOMMERCEPAGES', 'E-commerce: Non-product e-commerce pages') .
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
        return SiteTree::get()->filter('ClassName', [CartPage::class, AccountPage::class, ProductGroupSearchPage::class, CheckoutPage::class, OrderConfirmationPage::class] + (array) $this->Config()->get('additional_classnames'));
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'FullName' => [
                'title' => _t('EcommerceSideReport.BUYABLE_NAME', Product::class),
                'link' => true,
            ],
        ];
    }

    public function getReportField()
    {
        $field = parent::getReportField();
        $config = $field->getConfig();
        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
        $exportButton->setExportColumns($field->getColumns());

        return $field;
    }
}
