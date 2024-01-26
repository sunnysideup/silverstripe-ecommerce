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
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 *
 * @property string $Title
 * @property string $Data
 * @property int $ProductGroupID
 * @method \Sunnysideup\Ecommerce\Pages\ProductGroup ProductGroup()
 */
class ProductGroupSearchTable extends DataObject implements EditableEcommerceObject, Flushable
{
    protected static $already_removed_cache = [];
    private static $table_name = 'ProductGroupSearchTable';

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
    private static $singular_name = 'Product Group Search Entry';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Product Group Search Entries';

    public static function flush()
    {
        if (Security::database_is_ready()) {
            $tables = DB::table_list();
            if (in_array('ProductGroupSearchTable', $tables, true)) {
                DB::query('DELETE FROM ProductGroupSearchTable WHERE ProductGroupID = 0');
                DB::query(
                    '
                    DELETE ProductGroupSearchTable FROM ProductGroupSearchTable
                    LEFT JOIN ProductGroup_Live ON ProductGroup_Live.ID = ProductGroupSearchTable.ProductGroupID
                    WHERE ProductGroup_Live.ID IS NULL'
                );
            }
        }
    }

    public static function add_product_group($productGroup, array $dataAsArray)
    {
        $dataAsString = Sanitizer::html_array_to_text($dataAsArray);
        if ($productGroup->ID && $productGroup->ShowInSearch) {
            $filter = ['ProductGroupID' => $productGroup->ID];
            $obj = ProductGroupSearchTable::get()->filter($filter)->first();
            if (! $obj) {
                $obj = ProductGroupSearchTable::create($filter);
            }
            $obj->Title = Sanitizer::html_to_text($productGroup->Title . ' ' . $productGroup->AlternativeProductGroupNames);
            $obj->Data = $dataAsString;
            $obj->write();
        } else {
            self::remove_product_group($productGroup);
        }
    }

    public static function remove_product_group($productGroup)
    {
        if (empty(self::$already_removed_cache[$productGroup->ID])) {
            $obj = ProductGroupSearchTable::get()->filter(['ProductGroupID' => $productGroup->ID])->first();
            if ($obj) {
                $obj->delete();
                self::$already_removed_cache[$productGroup->ID] = true;
            }
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
