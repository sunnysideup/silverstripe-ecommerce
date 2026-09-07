<?php

namespace Sunnysideup\Ecommerce\Api;

use IteratorAggregate;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Connect\Query;
use SilverStripe\ORM\DB;
use SilverStripe\View\ArrayData;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

use function Clue\StreamFilter\fun;

/**
 * @description: Sometimes you need a large collection of products
 * returned as an array or ArrayList. Using the ORM can be inefficient to retrieve these collections.
 * This class is designed to be extended and allows you to retreive your desired product collection
 * using db query or whatever method you find to be most efficient.
 */
abstract class ProductCollection
{
    protected $additionalPredeterminedFilters = [];

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

    public function setAdditionalPredeterminedFilters(array $filters)
    {
        $this->additionalPredeterminedFilters = $filters;
        return $this;
    }

    protected function getAdditionalPredeterminedFiltersWhere(): string
    {
        $controller = Controller::curr();
        if ($controller) {
            $request = $controller->getRequest();
            if ($request) {
                $parentId = intval($this->additionalPredeterminedFilters['parentid'] ?? 0);
                $internalItemIDs = $this->additionalPredeterminedFilters['internalitemsid'] ?? null;
                if ($parentId || $internalItemIDs) {
                    $whereArray = [];
                    $stage = '_Live'; // always live
                    if (is_array($internalItemIDs) && !empty($internalItemIDs)) {
                        $internalItemIDs = Convert::raw2sql($internalItemIDs);
                        $internalItemIDs = array_map(
                            function ($id) {
                                return str_replace('"', '', $id);
                            },
                            $internalItemIDs
                        );
                        $whereArray = '"Product' . $stage . '"."ID" IN (\'' . implode("','", $internalItemIDs) . '\')';
                    }
                    if ($parentId) {
                        $parent = ProductGroup::get()->byID($parentId);
                        if ($parent) {
                            $productIds = $parent->getProducts()->columnUnique();
                            $whereArray[] = '"Product' . $stage . '"."ID" IN (\'' . implode("','", $productIds) . '\')';
                        }
                    }
                    return !empty($whereArray) ? implode(' AND ', $whereArray) : '';
                }
            }
        }
        return '';
    }
    protected function getWhereArrayForSql(array|string|null $where = ''): array
    {
        $array = $this->standardiseToArray($where);
        return $array;
    }

    protected function standardiseToArray(array|string|null $where = ''): array
    {
        if (! is_array($where)) {
            $where = [$where];
        }
        $where[] = $this->getAdditionalPredeterminedFiltersWhere();
        $where = array_filter($where);
        return array_unique($where);
    }

    public function getSQL(?string $where = ''): string
    {
        $array = $this->getWhereArrayForSql($where);

        $stage = '_Live'; // always live
        if ($array) {
            $where = '(' . implode(' AND ', $array) . ') AND ';
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
