<?php
/**
 * @description: Sometimes you need a large collection of products
 * returned as an array or ArrayList. Using the ORM can be inefficient to retrieve these collections.
 * This class is designed to be extended and allows you to retreive your desired product collection
 * using db query or whatever method you find to be most efficient.   
 **/
class ProductCollection
{
    public static function getArrayList()
    {
        $arrayList = new ArrayList();

        $productsArray = self::getArray();

        foreach($productsArray as $id => $className) {
            $arrayList->push($className::get_custom_data($id));
        }

        return $arrayList;
    }

    public static function getArray() : array
    {
        $array = [];

        $products = DB::query(self::getSQL());

        foreach ($products as $product) {
            $array[$product['ProductID']] =  $product['ClassName'];
        }

        return $array;    
    }

    public static function getSQL() : string
    {
       
        $sql = '
            SELECT
                "SiteTree_Live"."ID" ProductID,
                "SiteTree_Live"."ClassName" ClassName
            FROM
                "SiteTree_Live"
            INNER JOIN
                "Product_Live" ON "SiteTree_Live"."ID" = "Product_Live"."ID"
            WHERE
                "Product_Live"."AllowPurchase" = 1
            ;
        ';

        return $sql;
    }

    public static function checkForSemiColumn($item) : string
    {
        $item = str_replace(';', ',', $item);
        $item = str_replace("\r", ' ', $item);
        $item = str_replace("\n", ' ', $item);
        $item = str_replace("\t", ' ', $item);
        return $item;
    }
}