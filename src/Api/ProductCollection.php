<?php

namespace Sunnysideup\Ecommerce\Api;

use IteratorAggregate;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Connect\Query;
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
    public function getArrayList(): ArrayList
    {
        $arrayList = ArrayList::create();

        $products = $this->getArrayFull();
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
     */
    abstract public function getArrayFull(?string $where = ''): array;

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
     * @return IteratorAggregate
     */
    public function getArrayBasic(?string $where = '')
    {
        return DB::query($this->getSQL($where))->getIterator();
    }

    public function getSQL(?string $where = ''): string
    {
        $stage = '_Live'; // always live
        if ($where) {
            $where = '(' . $where . ') AND ';
        }
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
                ("Product' . $stage . '"."AllowPurchase" = 1)
            ;
        ';
    }
}
