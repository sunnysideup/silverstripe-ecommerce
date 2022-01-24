<?php

namespace Sunnysideup\Ecommerce\Control;

use Level51\AjaxSelectField\AjaxSelectField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Pages\Product;

class QuickUpdates extends Controller
{
    protected $isList = false;

    protected $currentItemID = 0;

    private static $url_segment = 'admin/ecommerce/quick-updates';

    private static $allowed_actions = [
        'index' => 'SHOPASSISTANTS',
        'doform' => 'SHOPASSISTANTS',
        'done' => 'SHOPASSISTANTS',
        'list' => 'SHOPASSISTANTS',
        'updateone' => 'SHOPASSISTANTS',
        'MyForm' => 'SHOPASSISTANTS',
    ];

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

    public function IsList(): bool
    {
        return $this->isList;
    }

    public function ListLink(): string
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

    public function Parent(): self
    {
        return Injector::inst()->get(self::class);
    }

    public function MyForm()
    {
        $fields = new FieldList(
        );

        $actions = new FieldList(
            FormAction::create('doform')->setTitle('Submit')
        );

        $required = new RequiredFields();

        return new Form($this, 'MyForm', $fields, $actions, $required);
    }

    public function Menu(): ArrayList
    {
        $classes = ClassInfo::subclassesFor(QuickUpdates::class, false);
        $al = ArrayList::create();
        foreach ($classes as $class) {
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

    public function Now(): string
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

    public function MyProduct()
    {
        $session = $this->getRequest()->getSession();
        $id = $session->get('LastProductUpdated');
        if ($id) {
            $session->set('LastProductUpdated', 0);
            $product = Product::get_by_id((int) $id);
            if ($product) {
                return DBField::create_field(
                    'HTMLText',
                    '<p class="message success">
                        Updated <a href="' . $product->Link() . '" target="_blank">' . $product->FullName . '</a>
                    </p>
                    <p>
                        <a href="' . $this->Link('updateone/' . $product->ID) . '">Add More</a> /
                        <a href="' . $this->Link('list') . '">Review List</a> / Choose another product below ...
                    </p>'
                );
            }
        }
    }

    protected function init()
    {
        parent::init();
        Requirements::javascript('silverstripe/admin: thirdparty/jquery/jquery.js');
    }

    /**
     * @param int $id
     *
     * @return AjaxSelectField|DropdownField
     */
    protected function productLookupField(string $name, string $title, ?int $id = 0)
    {
        $product = Product::get()->byID($id);
        if ($product) {
            return DropdownField::create(
                'ParentProductID',
                'Product',
            )
                ->setSource([$product->ID => $product->FullName])
                ->setHasEmptyDefault(false)
                ->setValue($product->ID)
            ;
        }
        $callBackFx = function ($query, $request) {
            $filter = [
                'Title:PartialMatch' => '__QUERY__',
                'InternalItemID:PartialMatch' => '__QUERY__',
            ];
            $list = Product::get()
                ->filter(['AllowPurchase' => true])
                ->sort('InternalItemID')
            ;
            // This part is only required if the idOnlyMode is active
            foreach ($filter as $key => $value) {
                if ($query) {
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

    protected function myLookupField($name, $title, $callBackFx): AjaxSelectField
    {
        return AjaxSelectField::create($name, $title)
            ->setMinSearchChars(3)
            ->setPlaceholder('find ...' . $title)
            ->setIdOnlyMode(true)
            ->setSearchCallback($callBackFx)
        ;
    }

    protected function productList(): SS_List
    {
        $array = [];
        $al = ArrayList::create();
        $step = 50;
        $maxItems = 100;
        $doneItems = 0;
        for ($i = 0; $i < $maxItems; ++$i) {
            $products = Product::get()
                ->filter(['AllowPurchase' => true])
                ->sort('Price DESC')
                ->limit($step, $i * $step)
            ;
            foreach ($products as $product) {
                $test = $this->isIncludedInListForProduct($product);
                if (true === $test) {
                    $al->push(new ArrayData([
                        'Item' => $product,
                        'Link' => $this->Link('updateone/' . $product->ID),
                    ]));
                    ++$doneItems;
                }
                if ($doneItems > $maxItems) {
                    $i = $maxItems + 1;
                }
            }
        }

        return $al;
    }

    protected function isIncludedInListForProduct(Product $product): bool
    {
        return true;
    }
}
