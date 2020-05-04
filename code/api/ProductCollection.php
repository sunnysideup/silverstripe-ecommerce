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

        $products = Product::get();

        foreach($products as $product) {
            $arrayList->push($product);
        }

        return $arrayList;
    }

    public static function getArray()
    {
        $array = [];

        $products = Product::get();

        foreach ($products as $product) {
            $array[] = [
                'ID' => $product->ID,
                'Title' => $product->Title
            ]
        }

        return $array;    
    }
}