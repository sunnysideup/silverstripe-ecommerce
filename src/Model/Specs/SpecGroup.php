<?php

namespace Sunnysideup\Ecommerce\Model\Specs;

use DateTime;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBPercentage;
use Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes\SpecValueBoolean;
use Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes\SpecValueDate;
use Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes\SpecValueDateTime;
use Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes\SpecValueHTMLText;
use Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes\SpecValueNumber;
use Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes\SpecValuePercentage;
use Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes\SpecValueText;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

class SpecGroup extends DataObject
{
    public static function get_type_from_value(mixed $value): string
    {
        return match (true) {
            is_bool($value) || $value instanceof DBBoolean => 'Boolean',
            is_int($value) || is_float($value) || $value instanceof DBFloat => 'Number',
            $value instanceof DBDate => 'Date',
            $value instanceof DateTime || $value instanceof DBDatetime => 'DateTime',
            $value instanceof DBPercentage => 'Percentage',
            strip_tags($value) !== $value || $value instanceof DBHTMLText => 'HTMLText',
            default => 'Text',
        };
    }

    public static function find_or_create($title, $type = 'Text', ?SpecGroup $specGroup = null): SpecGroup
    {
        $filter = [
            'Title' => $title,
            'ParentID' => $specGroup?->ID ?? 0,
        ];
        $obj = SpecGroup::get()->filter($filter)->first();
        if ($obj) {
            if ($type !== $obj->Type) {
                $obj->Type = $type;
                $obj->write();
            }
            return $obj;
        }
        $obj = SpecGroup::create($filter);
        $obj->Type = $type ?: 'Text';
        $obj->write();
        return $obj;
    }


    private static $table_name = 'SpecGroup';

    private static $db = [
        'Title' => 'Varchar',
        'Type' => 'Enum("Text,Boolean,Number,Date,DateTime,HTMLText,Percentage", "Text")'
    ];

    /**
     * Maps each type to its SpecValue class.
     * Can be customised via YAML config.
     */
    private static $type_classes = [
        'Text' => SpecValueText::class,
        'Boolean' => SpecValueBoolean::class,
        'Number' => SpecValueNumber::class,
        'Date' => SpecValueDate::class,
        'DateTime' => SpecValueDateTime::class,
        'HTMLText' => SpecValueHTMLText::class,
        'Percentage' => SpecValuePercentage::class,
    ];

    private static $has_one = [
        'Parent' => SpecGroup::class,
    ];
    private static $has_many = [
        'Children' => SpecGroup::class,
    ];

    private static $many_many = [
        'SpecValues' => SpecValue::class,
        'DefaultForCategories' => ProductGroup::class,
    ];

    private static $summary_fields = [
        'Parent.Title' => 'Grouped Under',
        'Children.Count' => 'Number of Child Spec Groups',
        'SpecValues.Count' => 'Number of Spec Values',
        'DefaultForCategories.Count' => 'Number of Default For Categories'
    ];

    private static $indexes = [
        'Title' => [
            'type' => 'unique',
            'columns' => [
                'Title',
                'ParentID',
            ],
        ]
    ];
    public function getTypeCssClass(): string
    {
        return strtolower($this->Type) . '-class';
    }

    public function getSpecValueClass(): string
    {
        $classes = $this->config()->get('type_classes');
        return $classes[$this->Type] ?? $classes['Text'];
    }

    public function createSpecValue(): SpecValue
    {
        $class = $this->getSpecValueClass();
        $obj = $class::create();
        $obj->ParentID = $this->ID;
        return $obj;
    }
}
