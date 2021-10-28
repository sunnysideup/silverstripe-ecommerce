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

/**
 * This dataobject
 * saves search replacements
 * as in Smoogle will be replaced by Google.
 */
class ProductSearchTable extends DataObject implements EditableEcommerceObject
{
    private static $table_name = 'ProductSearchTable';

    public static function get_results($keywords, ?array $preIdFilter = [], ?int $limit = 9999) : array
    {
        $listA = self::get_results_inner('Title', $keywords, $preIdFilter, $limit);
        $count = count($listA);
        if($count >= $limit) {
            return $listA;
        }
        $limit = $limit - $count;
        $listB = self::get_results_inner('Data', $keywords, $preIdFilter, $limit);
        return $listA + $listB;
    }


    protected static function get_results_inner(string $field, $keywords, ?array $preIdFilter = [], ?int $limit = 9999) : array
    {
        $where = '';
        if(count($preIdFilter)) {
            $where = 'WHERE "ProductID" IN ('.implode(',', $preIdFilter).')';
        }
        //NATURAL LANGUAGE gave too many results
        $sql = '
            SELECT "ProductID",
            MATCH ("'.$field.'") AGAINST (\''.Convert::raw2sql($keywords).'\' IN BOOLEAN MODE) AS score
            FROM "ProductSearchTable"
            '.$where.'
            ORDER BY score DESC
            LIMIT '.$limit.';';
        return DB::query($sql)->keyedColumn();
    }

    public static function add_product($product, array $dataAsArray, ?bool $onlyShowProductsThatCanBePurchased = true) {
        $dataAsString = strtolower(trim(preg_replace('/\s+/',' ', strip_tags(
            implode(
                ' ',
                $dataAsArray
                )
        ))));
        if($product->ID && (!$onlyShowProductsThatCanBePurchased || $product->AllowPurchase)) {
            $filter = ['ProductID' => $product->ID];
            $obj = ProductSearchTable::get()->filter($filter)->first();
            if(! $obj) {
                $obj = ProductSearchTable::create($filter);
            }
            $obj->Title = strtolower($product->Title);
            $obj->Data = $dataAsString;
            $obj->write();
        } else {
            $obj = ProductSearchTable::get()->byId($product->ID);
            if($obj) {
                $obj->delete();
            }
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
        'Product.Title' => 'Search Alias (e.g. nz)',
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
    private static $plural_name = 'Product Search Data Entries';


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
