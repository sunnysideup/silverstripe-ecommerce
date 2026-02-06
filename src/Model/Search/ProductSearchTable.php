<?php

namespace Sunnysideup\Ecommerce\Model\Search;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Interfaces\GenericProductSearchBooster;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Traits\SearchTableTrait;

/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 *
 * @property string $Title
 * @property string $Data
 * @property int $ProductID
 * @method \Sunnysideup\Ecommerce\Pages\Product Product()
 */
class ProductSearchTable extends DataObject implements EditableEcommerceObject, Flushable
{
    use SearchTableTrait;

    protected static $already_removed_cache = [];

    private static $table_name = 'ProductSearchTable';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Data' => 'Varchar(2048)',
        'Boost' => 'Decimal(4,2)',
    ];

    private static $has_one = [
        'Product' => Product::class,
    ];

    private static $indexes = [
        'UniqueProduct' => [
            'type' => 'unique',
            'columns' => [
                'ProductID',
            ],
        ],
        'SearchFields1' => [
            'type' => 'fulltext',
            'columns' => [
                'Title',
            ],
        ],
        'SearchFields2' => [
            'type' => 'fulltext',
            'columns' => [
                'Data',
            ],
        ],
    ];

    private static $summary_fields = [
        'Title' => 'Name',
        'LastEdited' => 'Last Edited',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Product Search Entry';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Product Search List';

    public static function flush()
    {
        if (Security::database_is_ready()) {
            $tables = DB::table_list();
            if (in_array('ProductSearchTable', $tables, true)) {
                DB::query('DELETE FROM ProductSearchTable WHERE ProductID = 0');
                DB::query(
                    '
                    DELETE ProductSearchTable FROM ProductSearchTable
                    LEFT JOIN Product_Live ON Product_Live.ID = ProductSearchTable.ProductID
                    WHERE Product_Live.ID IS NULL'
                );
            }
        }
    }

    public static function add_product($product, array $dataAsArray, ?bool $onlyShowProductsThatCanBePurchased = true)
    {
        if ($product->ID && $product->ShowInSearch && (! $onlyShowProductsThatCanBePurchased || $product->AllowPurchase)) {
            $filter = ['ProductID' => $product->ID];
            $obj = ProductSearchTable::get()->filter($filter)->first();
            if (! $obj) {
                $obj = ProductSearchTable::create($filter);
            }
            $obj->Title = Sanitizer::html_to_text($product->Title);
            $obj->Data = Sanitizer::html_array_to_text_limit_words($dataAsArray);
            $obj->Boost = $product->getSearchBoostCalculated() ?: 0;
            $generalBoosts = self::generic_boosts();
            foreach ($generalBoosts as $boostClass) {
                $obj->Boost += $boostClass->getBoostValueForProduct($product) ?: 0;
            }
            $obj->write();
        } else {
            self::remove_product($product);
        }
    }

    public static function remove_product($product)
    {
        if (empty(self::$already_removed_cache[$product->ID])) {
            $obj = ProductSearchTable::get()->filter(['ProductID' => $product->ID])->first();
            if ($obj) {
                $obj->delete();
                self::$already_removed_cache[$product->ID] = true;
            }
        }
    }

    protected static array $_generic_boosts;

    protected static function generic_boosts(): array
    {
        if (! isset(self::$_generic_boosts)) {
            self::$_generic_boosts = [];
            $classes = ClassInfo::implementorsOf(GenericProductSearchBooster::class);
            foreach ($classes as $class) {
                $instance = Injector::inst()->get($class);
                if ($instance instanceof GenericProductSearchBooster) {
                    self::$_generic_boosts[] = $instance;
                }
            }
        }
        return self::$_generic_boosts;
    }
}
