<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\AccountPage;
use Sunnysideup\Ecommerce\Pages\CartPage;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;
use Sunnysideup\Ecommerce\Pages\OrderConfirmationPage;
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
 */
class EcommerceSideReportEcommercePages extends Report
{


    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 6999;
    }

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
        return _t('EcommerceSideReport.ECOMMERCEPAGES', 'E-commerce: Non-product e-commerce pages');
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
     * working out the items.
     *
     * @param null|mixed $params
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function sourceRecords($params = null)
    {
        $array = [
            CartPage::class,
            AccountPage::class,
            ProductGroupSearchPage::class,
            CheckoutPage::class,
            OrderConfirmationPage::class,
        ] +
        (array) $this->Config()->get('additional_classnames');

        return SiteTree::get()->filter(['ClassName' => $array]);
    }


}
