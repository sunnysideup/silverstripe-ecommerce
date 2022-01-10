<?php

namespace Sunnysideup\Ecommerce\Model\Search;

use SilverStripe\Core\Flushable;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 */
class ProductSearchTable extends DataObject implements EditableEcommerceObject, Flushable
{
    private static $table_name = 'ProductSearchTable';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Data' => 'Varchar(2048)',
    ];

    private static $has_one = [
        'Product' => Product::class,
    ];

    private static $create_table_options = [
        MySQLSchemaManager::ID => 'ENGINE=MyISAM',
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
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Product Search Data';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Product Search List';

    public static function flush()
    {
        if(Security::database_is_ready()) {
            $tables = DB::table_list();
            if (in_array('ProductGroupSearchTable', $tables)) {
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
        $dataAsString = Sanitizer::html_array_to_text($dataAsArray);
        if ($product->ID && $product->ShowInSearch && (! $onlyShowProductsThatCanBePurchased || $product->AllowPurchase)) {
            $filter = ['ProductID' => $product->ID];
            $obj = ProductSearchTable::get()->filter($filter)->first();
            if (! $obj) {
                $obj = ProductSearchTable::create($filter);
            }
            $obj->Title = Sanitizer::html_to_text($product->Title);
            $obj->Data = $dataAsString;
            $obj->write();
        } else {
            self::remove_product($product);
        }
    }

    public static function remove_product($product)
    {
        $obj = ProductSearchTable::get_by_id($product->ID);
        if ($obj) {
            $obj->delete();
        }
    }

    /**
     * link to edit the record.
     *
     * @param null|string $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }
}
