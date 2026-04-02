<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\PaginatedList;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Sunnysideup\AjaxSelectField\AjaxSelectField;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Class \Sunnysideup\Ecommerce\Control\QuickUpdates
 */
class QuickUpdates extends Controller
{
    protected $isList = false;

    protected $currentItemID = 0;

    private static $url_segment = 'admin/ecommerce/quick-updates';

    private static $allowed_actions = [
        'index' => true,
        'doform' => true,
        'done' => true,
        'list' => true,
        'updateone' => true,
        'MyForm' => true,
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

    protected function processOrders($orders)
    {
        foreach ($orders as $order) {
            $order->tryToFinaliseOrder();
        }
    }

    public function MyForm()
    {
        $fields = new FieldList([]);

        $actions = new FieldList(
            [
                FormAction::create('doform')->setTitle('Submit'),
            ]
        );

        $required = new RequiredFieldsValidator([]);

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
                        Updated
                        <a href="' . $product->CMSEditLink() . '" target="_blank">✎</a>
                        <a href="' . $product->Link() . '" target="_blank">' . $product->FullName . '</a>
                    </p>
                    <p>
                        <a href="' . $this->Link('updateone/' . $product->ID) . '">Edit Again</a> //
                        <a href="' . $this->Link('list') . '">Review List</a> //
                        Choose another below ...
                    </p>'
                );
            }
        }
    }

    protected function init()
    {
        parent::init();
        $allowedActions = $this->Config()->get('allowed_actions');
        $securityCheck = $allowedActions['index'] ?? 'ADMIN';
        if (! Permission::check($securityCheck)) {
            Security::permissionFailure($this);
        }
        Requirements::javascript('https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js');
        if (get_class($this) === QuickUpdates::class) {
            return $this->httpError(404, 'Please choose a specific update.');
        }
    }

    /**
     * @return AjaxSelectField|DropdownField
     */
    protected function productLookupField(string $name, string $title, ?int $id = 0, ?bool $offerRemoveOption = false)
    {
        $product = Product::get()->byID($id);
        if ($product) {
            $source = [$product->ID => $product->FullName];
            if ($offerRemoveOption) {
                $source = [-1 * $product->ID => '--- remove ---'] + $source;
            }
            return DropdownField::create(
                $name,
                $title,
            )
                ->setSource($source)
                ->setHasEmptyDefault(false)
                ->setValue($product->ID)
            ;
        }
        $callBackFx = function ($query, $request) {

            $list = Product::get()
                ->filter(['AllowPurchase' => true])
                ->sort(['InternalItemID' => 'ASC']);
            if ($query) {
                $query = Convert::raw2sql($query);
                $filter = [
                    'Title:PartialMatch' => $query,
                    'InternalItemID:PartialMatch' => $query,
                ];
                $list = $list->filterAny($filter);
            }
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
            ->setPlaceholder('Start typing and wait for list to appear after a few characters ... ')
            ->setIdOnlyMode(true)
            ->setSearchCallback($callBackFx)
        ;
    }

    protected function getMaxItems(): int
    {
        return 1000;
    }

    public function ListItems(): PaginatedList
    {
        return (new PaginatedList($this->productList(), $this->getRequest()))
            ->setPageLength(50);
    }

    protected function productList(): DataList
    {
        $products = Product::get()
            ->filter(['AllowPurchase' => true])
            ->sort(['Price' => 'DESC'])
            ->limit($this->getMaxItems());

        return $this->isIncludedInListForProductSqlChanges($products);
    }

    protected function isIncludedInListForProductSqlChanges(DataList $list): DataList
    {
        return $list;
    }
}
