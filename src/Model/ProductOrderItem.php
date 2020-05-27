<?php

namespace Sunnysideup\Ecommerce\Model;

use SilverStripe\Core\Config\Config;
use SilverStripe\View\SSViewer;

class ProductOrderItem extends OrderItem
{
    /**
     * standard SS method.
     *
     * @var array
     */
    private static $api_access = [
        'view' => [
            'CalculatedTotal',
            'TableTitle',
            'TableSubTitleNOHTML',
            'Name',
            'TableValue',
            'Quantity',
            'BuyableID',
            'BuyableClassName',
            'Version',
            'UnitPrice',
            'Total',
            Order::class,
            'InternalItemID',
        ],
    ];

    /**
     * Overloaded Product accessor method.
     *
     * Overloaded from the default has_one accessor to
     * retrieve a product by it's version, this is extremely
     * useful because we can set in stone the version of
     * a product at the time when the user adds the item to
     * their cart, so if the CMS admin changes the price, it
     * remains the same for this order.
     *
     * @param bool $current If set to TRUE, returns the latest published version of the Product,
     *                      If set to FALSE, returns the set version number of the Product
     *                      (instead of the latest published version)
     *
     * @return Product object
     */
    public function Product($current = false)
    {
        return $this->Buyable($current);
    }

    /**
     * @param OrderItem $orderItem
     *
     * @return bool
     **/
    public function hasSameContent(OrderItem $orderItem)
    {
        $parentIsTheSame = parent::hasSameContent($orderItem);

        return $parentIsTheSame && is_a($orderItem, $this->class);
    }

    /**
     *@return string
     **/
    public function TableTitle()
    {
        return $this->getTableTitle();
    }

    public function getTableTitle()
    {
        $tableTitle = _t('Product.UNKNOWN', 'Unknown Product');
        if ($product = $this->Product()) {
            Config::nest();
            Config::inst()->update(SSViewer::class, 'theme_enabled', true);
            $tableTitle = strip_tags($product->renderWith('Sunnysideup\Ecommerce\Includes\ProductTableTitle'));
            Config::unnest();
        }
        $updatedTableTitle = $this->extend('updateTableTitle', $tableTitle);
        if ($updatedTableTitle !== null && is_array($updatedTableTitle) && count($updatedTableTitle)) {
            $tableTitle = implode($updatedTableTitle);
        }

        return $tableTitle;
    }

    /**
     * @return string
     */
    public function TableSubTitle()
    {
        return $this->getTableSubTitle();
    }

    public function getTableSubTitle()
    {
        $tableSubTitle = '';
        if ($product = $this->Product()) {
            $tableSubTitle = $product->Quantifier;
        }
        $updatedSubTableTitle = $this->extend('updateSubTableTitle', $tableSubTitle);
        if ($updatedSubTableTitle !== null && is_array($updatedSubTableTitle) && count($updatedSubTableTitle)) {
            $tableSubTitle = implode('', $updatedSubTableTitle);
        }

        return $tableSubTitle;
    }

    /**
     * method for developers only
     * you can access it like this: /shoppingcart/debug/.
     *
     * @return string
     */
    public function debug()
    {
        $title = $this->TableTitle();
        $productID = $this->BuyableID;
        $productVersion = $this->Version;
        $html = parent::debug() . <<<HTML
            <h3>ProductOrderItem class details</h3>
            <p>
                <b>Title : </b>${title}<br/>
                <b>Product ID : </b>${productID}<br/>
                <b>Product Version : </b>${productVersion}
            </p>
HTML;
        $updatedHTML = $this->extend('updateDebug', $html);
        if ($updatedHTML !== null && is_array($updatedHTML) && count($updatedHTML)) {
            $html = implode('', $updatedHTML);
        }

        return $html;
    }
}
