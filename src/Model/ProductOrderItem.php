<?php

namespace Sunnysideup\Ecommerce\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Sunnysideup\Ecommerce\Api\SetThemed;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Class \Sunnysideup\Ecommerce\Model\ProductOrderItem
 */
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
            'Order',
            'InternalItemID',
        ],
    ];

    private static $casting = [
        'TableTitle' => 'HTMLText',
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
     * @return DataObject|Product object
     */
    public function Product($current = false)
    {
        return $this->getBuyableCached($current);
    }

    /**
     * @return bool
     */
    public function hasSameContent(OrderItem $orderItem)
    {
        $parentIsTheSame = parent::hasSameContent($orderItem);

        return $parentIsTheSame && is_a($orderItem, $this->ClassName);
    }

    /**
     * @return DBHTMLText
     */
    public function TableTitle(): string
    {
        return $this->getTableTitle();
    }

    public function getTableTitle(): string
    {
        if ($this->priceHasBeenFixed() && $this->Name) {
            return (string) $this->Name;
        }
        $tableTitle = _t('Product.UNKNOWN', 'Unknown Product');
        $product = $this->Product();
        if ($product) {
            SetThemed::start();
            $tableTitle = strip_tags((string) $product->renderWith('Sunnysideup\Ecommerce\Includes\ProductTableTitle'));
            SetThemed::end();
        } else {
            // last resort ...
            $rows = DB::query(
                '
                SELECT Title
                FROM SiteTree_Versions
                WHERE RecordID = ' . $this->BuyableID . '
                ORDER BY ID DESC LIMIT 1
                '
            );
            foreach ($rows as $row) {
                $tableTitle = $row['Title'];
            }
        }
        $updatedTableTitle = $this->extend('updateTableTitle', $tableTitle);
        if (null !== $updatedTableTitle && is_array($updatedTableTitle) && count($updatedTableTitle)) {
            $tableTitle = implode('', $updatedTableTitle);
        }

        return trim($tableTitle);
    }

    public function getTableSubTitle(): string
    {
        if ($this->priceHasBeenFixed() && $this->TableSubTitleFixed) {
            return (string) $this->TableSubTitleFixed;
        }
        $tableSubTitle = '';
        $product = $this->Product();
        if ($product) {
            $tableSubTitle = $product->Quantifier;
        }
        $updatedSubTableTitle = $this->extend('updateSubTableTitle', $tableSubTitle);
        if (null !== $updatedSubTableTitle && is_array($updatedSubTableTitle) && count($updatedSubTableTitle)) {
            $tableSubTitle = implode('', $updatedSubTableTitle);
        }

        return (string) $tableSubTitle;
    }

    /**
     * method for developers only
     * you can access it like this: /shoppingcart/debug/.
     *
     * @return string
     */
    public function debug()
    {
        $title = $this->getTableTitle();
        $productID = $this->BuyableID;
        $productVersion = $this->Version;
        $html = parent::debug() . <<<HTML
            <h3>ProductOrderItem class details</h3>
            <p>
                <b>Title : </b>{$title}<br/>
                <b>Product ID : </b>{$productID}<br/>
                <b>Product Version : </b>{$productVersion}
            </p>
HTML;
        $updatedHTML = $this->extend('updateDebug', $html);
        if (null !== $updatedHTML && is_array($updatedHTML) && count($updatedHTML)) {
            $html = implode('', $updatedHTML);
        }

        return $html;
    }
}
