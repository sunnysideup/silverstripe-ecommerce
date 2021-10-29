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

use Sunnysideup\Ecommerce\Pages\ProductGroup;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use SilverStripe\Core\Flushable;
/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 */
class ProductGroupSearchTable extends DataObject implements EditableEcommerceObject, Flushable
{
    private static $table_name = 'ProductGroupSearchTable';

    public static function flush()
    {
        DB::query('DELETE FROM ProductGroupSearchTable WHERE ProductGroupID = 0');
    }

    public static function add_product_group($productGroup, array $dataAsArray) {
        $dataAsString = strtolower(trim(preg_replace('/\s+/',' ', strip_tags(
            implode(
                ' ',
                $dataAsArray
                )
        ))));
        if($productGroup->ID && $productGroup->ShowInSearch) {
            $filter = ['ProductGroupID' => $productGroup->ID];
            $obj = ProductGroupSearchTable::get()->filter($filter)->first();
            if(! $obj) {
                $obj = ProductGroupSearchTable::create($filter);
            }
            $obj->Title = strtolower($productGroup->Title);
            $obj->Data = $dataAsString;
            $obj->write();
        } else {
            self::remove_product_group($productGroup);
        }
    }

    public static function remove_product_group($productGroup)
    {
        $obj = ProductGroupSearchTable::get()->byId($productGroup->ID);
        if($obj) {
            $obj->delete();
        }
    }


    private static $db = [
        'Title' => 'Varchar(255)',
        'Data' => 'Varchar(2048)',
    ];

    private static $has_one = [
        'ProductGroup' => ProductGroup::class,
    ];

    private static $create_table_options = [
        MySQLSchemaManager::ID => 'ENGINE=MyISAM',
    ];

    private static $indexes = [
        'UniqueProduct' => [
            'type' => 'unique',
            'columns' => [
                'ProductGroupID',
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
    private static $singular_name = 'Product Group Search Data';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Product Group Search Data Entries';


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


}
