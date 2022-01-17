<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;

use SilverStripe\Core\ClassInfo;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBDatetime;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

use Sunnysideup\Ecommerce\Pages\Product;

use Level51\AjaxSelectField\AjaxSelectField;

class QuickUpdates extends Controller
{
    private static $url_segment = 'admin/ecommerce/quick-updates';

    private static $allowed_actions = [
        'index' => 'SHOPASSISTANTS',
        'do' => 'SHOPASSISTANTS',
        'done' => 'SHOPASSISTANTS',
        'list' => 'SHOPASSISTANTS',
        'updateone' => 'SHOPASSISTANTS',
        'MyForm' => 'SHOPASSISTANTS',
    ];

    protected $isList = false;

    protected $currentItemID = 0;

    public function updateone($request)
    {
        $this->currentItemID = (int) $request->param('ID');
        return [];
    }

    public function list($request)
    {
        $this->isList = true;
        return [];
    }

    public function IsList() : bool
    {
        return $this->isList;
    }

    public function ListLink() : string
    {
        return $this->Link('list');
    }

    public function Title(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return 'E-commerce Quick Updates';
    }

    public function Parent() : self
    {
        return Injector::inst()->get(self::class);
    }

    public function MyForm()
    {
        $fields = new FieldList(
        );

        $actions = new FieldList(
            FormAction::create('do')->setTitle('Submit')
        );

        $required = new RequiredFields();

        return new Form($this, 'MyForm', $fields, $actions, $required);
    }

    public function Menu() : ArrayList
    {
        $classes = ClassInfo::subclassesFor(QuickUpdates::class, false);
        $al = ArrayList::create();
        foreach($classes as $class) {
            $obj = Injector::inst()->get($class);
            $al->push(
                ArrayData::create(
                    [
                        'Title' => $obj->getTitle(),
                        'Link' => $obj->Link(),
                    ]
                )
            );
        }
        return $al;
    }

    public function Now() : string
    {
        return DBDatetime::now()->Nice();
    }

    public function index($request)
    {
        return $this->renderWith(static::class);
    }

    public function done($request)
    {
        return $this->renderWith(static::class);
    }


    protected function init()
    {
        parent::init();
        Requirements::javascript('silverstripe/admin: thirdparty/jquery/jquery.js');
    }

    /**
     *
     * @param  string   $name
     * @param  string   $title
     * @param  integer  $id
     * @return FormField
     */
    protected function productLookupField(string $name, string $title, ?int $id = 0)
    {
        $product = Product::get()->byID($id);
        if($product) {
            return DropdownField::create(
                'ParentProductID',
                'Product',
            )
                ->setSource([$product->ID => $product->FullName])
                ->setHasEmptyDefault(false)
                ->setValue($product->ID);
        } else {
            $callBackFx = function ($query, $request) {
                $filter = [
                    'Title:PartialMatch' => '__QUERY__',
                    'InternalItemID:PartialMatch' => '__QUERY__',
                ];
                $list = Product::get()
                    ->filter(['AllowPurchase' => true,])
                    ->sort('InternalItemID');
                // This part is only required if the idOnlyMode is active
                foreach($filter as $key => $value) {
                    if($query) {
                        $value = str_replace('__QUERY__', $query, $value);
                        $filter[$key] = $value;
                    } else {
                        unset($filter[$key]);
                    }
                }
                $list = $list->filterAny($filter);
                $results = [];
                foreach ($list as $obj) {
                    $results[] = [
                        'id' => $obj->ID,
                        'title' => $obj->FullName,
                    ];
                }

                return $results;
            };
            return $this->myLookupField($name, $title, $callBackFx);
        }
    }

    protected function myLookupField($name, $title, $callBackFx): AjaxSelectField
    {
        return AjaxSelectField::create($name, $title)
            ->setMinSearchChars(3)
            ->setPlaceholder('find ...'.$title)
            ->setIdOnlyMode(true)
            ->setSearchCallback($callBackFx)
        ;
    }
}
