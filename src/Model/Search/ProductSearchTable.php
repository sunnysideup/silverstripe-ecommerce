<?php

namespace Sunnysideup\Ecommerce\Model\Search;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

use Sunnysideup\Ecommerce\Pages\Product;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use SilverStripe\Core\Flushable;

/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 */
class ProductSearchTable extends DataObject implements EditableEcommerceObject, Flushable
{
    private static $table_name = 'ProductSearchTable';

    public static function flush()
    {
        $tables = DB::table_list();
        if(array_key_exists(strtolower('ProductGroupSearchTable'), $tables)) {
            DB::query('DELETE FROM ProductSearchTable WHERE ProductID = 0');
        }
    }


    public static function add_product($product, array $dataAsArray, ?bool $onlyShowProductsThatCanBePurchased = true) {
        $dataAsString = strtolower(trim(preg_replace('/\s+/',' ', strip_tags(
            implode(
                ' ',
                $dataAsArray
                )
        ))));
        if($product->ID && $product->ShowInSearch &&  (!$onlyShowProductsThatCanBePurchased || $product->AllowPurchase)) {
            $filter = ['ProductID' => $product->ID];
            $obj = ProductSearchTable::get()->filter($filter)->first();
            if(! $obj) {
                $obj = ProductSearchTable::create($filter);
            }
            $obj->Title = strtolower($product->Title);
            $obj->Data = $dataAsString;
            $obj->write();
        } else {
            self::remove_product($product);
        }
    }

    public static function remove_product($product)
    {
        $obj = ProductSearchTable::get()->byId($product->ID);
        if($obj) {
            $obj->delete();
        }
    }


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
