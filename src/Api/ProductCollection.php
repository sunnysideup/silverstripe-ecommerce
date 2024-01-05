<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\View\ArrayData;

/**
 * @description: Sometimes you need a large collection of products
 * returned as an array or ArrayList. Using the ORM can be inefficient to retrieve these collections.
 * This class is designed to be extended and allows you to retreive your desired product collection
 * using db query or whatever method you find to be most efficient.
 */
abstract class ProductCollection
{
    /**
     * @return ArrayList
     */
    public function getArrayList(): ArrayList
    {
        $arrayList = ArrayList::create();

        $products = $this->getArrayBasic();
        foreach ($products as $product) {
            $arrayList->push(
                ArrayData::create(
                    $product
                )
            );
        }
        return $arrayList;
    }

    /**
     * Allows you to extend the array with additional information.
     * If there is no need to extend getBasicArray method, then just return that.
     *
     * @return array
     */
    abstract public function getArrayFull(): array;

    /**
     * write like this:
     * ```php
     *      $array = [];
     *      $products = DB::query($this->getSQL());
     *      foreach ($products as $product) {
     *          // ensure special chars are converted to HTML entities for XML output
     *          // do other stuff!
     *          $array[] = $product;
     *      }
     *      return $array;
     *
     * @return array
     */
    public function getArrayBasic(): array
    {
        $array = [];
        $products = DB::query($this->getSQL());
        foreach ($products as $product) {
            // ensure special chars are converted to HTML entities for XML output
            // do other stuff!
            $array[] = $product;
        }
        return $array;
    }

    public function getSQL(?string $where = ''): string
    {
        $stage = '_Live'; // always live

        return '
            SELECT
                "SiteTree' . $stage . '"."ID" ProductID,
                "SiteTree' . $stage . '"."ClassName" ClassName
            FROM
                "SiteTree' . $stage . '"
            INNER JOIN
                "Product' . $stage . '" ON "SiteTree' . $stage . '"."ID" = "Product' . $stage . '"."ID"
            WHERE
                ' . $where . '
                "Product' . $stage . '"."AllowPurchase" = 1
            ;
        ';
    }
}
