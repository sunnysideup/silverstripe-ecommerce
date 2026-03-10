<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\SearchableDropdownField;

/**
 * Select A product using an ajax search field.
 * You can also use this for other classes.
 * But you will need to set the ClassName, labelFieldName and DataList.
 * Example on how to use this:
 * ```php
 *     ProductSelectField::create('ProductID', 'Product')
 *         ->setClassName(Product::class) // optional
 *         ->setLabelFieldName('FullName') // optional
 *         ->setIdFieldName('ID') // optional
 *         ->setDataList(Product::get()->filter(['AllowPurchase' => true])->sort(['FullName' => 'ASC'])); // optional
 */
class ProductSelectField2 extends SearchableDropdownField
{
}
