<?php

namespace Sunnysideup\Ecommerce\Model\Specs;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Abstract base class for specification values.
 * Each type (Text, Boolean, Number, etc.) has its own subclass.
 */
class SpecValue extends DataObject
{
    /**
     * Sets multiple specifications for a product.
     *
     * Example usage:
     * ```php
     * SpecValue::set_specs_for_product($product, [
     *     ['groups' => ['Colour'], 'value' => 'Red'],
     *     ['groups' => ['Dimensions', 'Width'], 'value' => 100],
     *     ['groups' => ['Features', 'Waterproof'], 'value' => true],
     * ]);
     * ```
     *
     * @param Product $product The product to set specs for
     * @param array $specs Array of specs, each with 'groups' (array) and 'value' (mixed)
     * @param bool $replaceExisting If true, removes all existing specs first; if false, adds/updates
     * @return array Array of created/updated SpecValue objects
     */
    public static function set_specs_for_product(Product $product, array $specs, bool $replaceExisting = false): array
    {
        if ($replaceExisting) {
            self::remove_all_specs_for_product($product);
        }

        $result = [];
        foreach ($specs as $spec) {
            $groups = $spec['groups'] ?? [];
            $value = $spec['value'] ?? null;
            if (!empty($groups) && $value !== null) {
                $specValue = self::create_spec_for_product($product, $value, $groups);
                if ($specValue) {
                    $result[] = $specValue;
                }
            }
        }
        return $result;
    }

    /**
     * Removes all spec values from a product.
     *
     * @param Product $product The product to remove specs from
     */
    public static function remove_all_specs_for_product(Product $product): void
    {
        $typeClasses = SpecGroup::config()->get('type_classes');
        foreach ($typeClasses as $class) {
            $specValues = $class::get()->filter('Products.ID', $product->ID);
            foreach ($specValues as $specValue) {
                $specValue->Products()->remove($product);
            }
        }
    }

    /**
     * Removes spec values from a product for a specific group hierarchy.
     *
     * @param Product $product The product to remove specs from
     * @param array $groups Group hierarchy to match
     */
    public static function remove_specs_for_product_by_group(Product $product, array $groups): void
    {
        if (empty($groups)) {
            return;
        }
        // Find the leaf group
        $parent = null;
        foreach ($groups as $groupTitle) {
            $filter = [
                'Title' => $groupTitle,
                'ParentID' => $parent?->ID ?? 0,
            ];
            $parent = SpecGroup::get()->filter($filter)->first();
            if (!$parent) {
                return; // Group hierarchy doesn't exist
            }
        }
        // Remove specs in this group from the product
        $class = $parent->getSpecValueClass();
        $specValues = $class::get()->filter([
            'ParentID' => $parent->ID,
            'Products.ID' => $product->ID,
        ]);
        foreach ($specValues as $specValue) {
            $specValue->Products()->remove($product);
        }
    }

    /**
     * Creates a specification for a given product based on the provided value and groups.
     * If the necessary spec groups do not exist, they will be created.
     * The first group provided in the group array will be considered the top-level parent.
     *
     * @param Product $product The product to attach the spec to
     * @param mixed $value The specification value
     * @param array $groups Hierarchy of group names (first = top-level)
     * @return SpecValue|null The created SpecValue, or null if no groups provided
     */
    public static function create_spec_for_product(Product $product, mixed $value, array $groups): ?SpecValue
    {
        if (empty($groups)) {
            return null;
        }
        $type = SpecGroup::get_type_from_value($value);
        $parent = null;
        foreach ($groups as $group) {
            $parent = SpecGroup::find_or_create($group, $type, $parent);
        }
        $specValue = self::find_or_create($value, $parent);
        $specValue->Products()->add($product);
        return $specValue;
    }
    public static function find_or_create($value, SpecGroup $specGroup): SpecValue
    {
        $class = $specGroup->getSpecValueClass();
        $filter = [
            'Value' => $value,
            'ParentID' => $specGroup->ID,
        ];
        $obj = $class::get()->filter($filter)->first();
        if ($obj) {
            return $obj;
        }
        $obj = $class::create($filter);
        $obj->write();
        return $obj;
    }

    private static $table_name = 'SpecValue';

    private static $db = [
        'AllowQuickFilter' => 'Boolean',
    ];

    private static $has_one = [
        'Parent' => SpecGroup::class,
    ];

    private static $many_many = [
        'Products' => Product::class
    ];

    private static $summary_fields = [
        'Title' => 'Value',
        'Parent.Title' => 'Grouped Under',
        'Products.Count' => 'Number of Products'
    ];

    private static $indexes = [];

    public function getTypeField(): string
    {
        return 'Value';
    }

    public function getTypeObjectClass(): string
    {
        $dbType = $this->config()->get('db')['Value'] ?? 'Varchar';
        // Strip any parameters e.g. "Varchar(255)" -> "Varchar"
        $dbType = preg_replace('/\(.*\)/', '', $dbType);
        return Injector::inst()->get($dbType, false, ['Name' => 'Value'])::class;
    }

    public function getTypeCSSClass(): string
    {
        return $this->Parent()->getTypeCssClass();
    }

    public function getTitle(): string
    {
        return (string) $this->getValue();
    }


    public function getValue()
    {
        return $this->Value;
    }

    public function setValue($value): static
    {
        $this->Value = $value;
        return $this;
    }


}
