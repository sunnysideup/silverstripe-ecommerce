<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;

/**
 * @description: Sometimes you need a large collection of products
 * returned as an array or ArrayList. Using the ORM can be inefficient to retrieve these collections.
 * This class is designed to be extended and allows you to retreive your desired product collection
 * using db query or whatever method you find to be most efficient.
 **/
abstract class ProductCollection
{
    /**
     * @return \SilverStripe\ORM\ArrayList
     */
    abstract public function getArrayList(): ArrayList;

    /**
     * @return array
     */
    abstract public function getArrayFull(): array;

    /**
     * @return array
     */
    public function getArrayBasic(): array
    {
        $array = [];

        $products = DB::query($this->getSQL());

        foreach ($products as $product) {
            $array[$product['ProductID']] = $product['ClassName'];
        }

        return $array;
    }

    public function getSQL(): string
    {
        $stage = '_Live';

        return '
            SELECT
                "SiteTree' . $stage . '"."ID" ProductID,
                "SiteTree' . $stage . '"."ClassName" ClassName
            FROM
                "SiteTree' . $stage . '"
            INNER JOIN
                "Product' . $stage . '" ON "SiteTree' . $stage . '"."ID" = "Product' . $stage . '"."ID"
            WHERE
                "Product' . $stage . '"."AllowPurchase" = 1
            ;
        ';
    }
}
