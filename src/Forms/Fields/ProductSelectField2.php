<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use ArrayAccess;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\SearchableDropdownField;
use SilverStripe\ORM\DataList;
use Sunnysideup\AjaxSelectField\AjaxSelectField;
use Sunnysideup\Ecommerce\Pages\Product;

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
 *
 */
class ProductSelectField2 extends SearchableDropdownField
{
}
