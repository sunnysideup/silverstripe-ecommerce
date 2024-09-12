<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use ArrayAccess;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
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
class ProductSelectField extends AjaxSelectField
{
    protected array $additionalFilter = [];

    protected string $className = Product::class;

    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }
    protected string $labelFieldName = 'FullName';

    public function setLabelFieldName(string $labelFieldName): self
    {
        $this->labelFieldName = $labelFieldName;

        return $this;
    }
    protected string $idFieldName = 'ID';

    public function setIdFieldName(string $idFieldName): self
    {
        $this->idFieldName = $idFieldName;

        return $this;
    }

    public function setDataList(DataList $dataList): self
    {
        $className = $this->className;
        $labelFieldName = $this->labelFieldName;
        $idFieldName = $this->idFieldName;
        $fx = function ($query, $request) use ($dataList, $className, $idFieldName, $labelFieldName): array {
            // This part is only required if the idOnlyMode is active
            if ($id = $request->getVar('id')) {
                $page = $className::get()->filter([$idFieldName => Convert::raw2sql($id)])->first();

                return [
                    'id' => $page->$idFieldName,
                    'title' => $page->$labelFieldName
                ];
            }

            $results = [];
            $products = $dataList;
            $products = $products->filter([$labelFieldName.':PartialMatch' => $query]);
            foreach ($products as $product) {
                $results[] = [ 'id' => $product->$idFieldName, 'title' => $product->$labelFieldName];
            }

            return $results;
        };

        $this->setSearchCallback($fx);

        return $this;
    }

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);
        $className = $this->className;
        $labelFieldName = $this->labelFieldName;
        $this
            ->setMinSearchChars(3)
            ->setPlaceholder('Type to search and select ... ')
            ->setIdOnlyMode(true)
            ->setDataList($className::get()->sort([$labelFieldName => 'ASC']));
    }
}
