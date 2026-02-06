<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

trait EcommerceProductReportTrait
{
    /**
     * not sure if this is used in SS3.
     *
     * @return string
     */
    public function group()
    {
        return _t('EcommerceSideReport.ECOMMERCEGROUP', 'Ecommerce');
    }

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 7000;
    }

    /**
     * working out the items.
     *
     * @param null|mixed $params
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function sourceRecords($params = null, $sort = null, $limit = null)
    {
        Versioned::set_stage(Versioned::DRAFT);

        // data class
        $className = ($params['ProductType'] ?? '');
        if ($className && class_exists($className)) {
            $list = $className::get()->filter(['ClassName' => $className]);
        } else {
            $className = $this->dataClass;
            $list = $className::get();
        }


        // final change to update
        if ($this->hasMethod('updateEcommerceList')) {
            $list = $this->updateEcommerceList($list);
        }

        $title = (string) Convert::raw2sql($params['Title'] ?? '');
        if ($title) {
            $list = $list->filterAny(['Title:PartialMatch' => $title, 'ProductBreadcrumb:PartialMatch' => $title, 'InternalItemID:PartialMatch' => $title, 'AlternativeProductNames:PartialMatch' => $title]);
        }

        $minPrice = (float) preg_replace('#[^0-9.\-]#', '', ($params['MinimumPrice'] ?? 0));
        if ($minPrice !== 0.0) {
            $list = $list->filter(['Price:GreaterThan' => $minPrice]);
        }

        $forSale = $params['ForSale'] ?? '';
        if ($forSale) {
            $forSaleFilter = null;
            if ('Yes' === $forSale) {
                $forSaleFilter = 1;
            } elseif ('No' === $forSale) {
                $forSaleFilter = 0;
            }

            if (null !== $forSaleFilter) {
                $list = $list->filter(['AllowPurchase' => $forSaleFilter]);
            }
        }

        $changedInTheLastXDays = (int) ($params['ChangedInTheLastXDays'] ?? 0);
        if ($changedInTheLastXDays !== 0) {
            $list = $list->where(['"LastEdited" >= DATE_ADD(CURDATE(), INTERVAL -' . (int) $changedInTheLastXDays . ' DAY)']);
        }

        $createdInTheLastXDays = (int) ($params['CreatedInTheLastXDays'] ?? 0);
        if ($createdInTheLastXDays !== 0) {
            $list = $list->where(['"Created" >= DATE_ADD(CURDATE(), INTERVAL -' . (int) $createdInTheLastXDays . ' DAY)']);
        }
        $parentID = intval(($params['ParentID'] ?? 0));
        if ($parentID !== 0) {
            $list = $list->filter(['ParentID' => $parentID]);
        }

        // filter
        if ($this->hasMethod('getEcommerceFilter')) {
            $filter = $this->getEcommerceFilter();
            if (! empty($filter)) {
                $list = $list->filter($filter);
            }
        }

        // fancy filter
        if ($this->hasMethod('getEcommerceWhere')) {
            $where = $this->getEcommerceWhere();
            if (! empty($where)) {
                $list = $list->where($where);
            }
        }

        //sort
        $sort = null;
        if ($this->hasMethod('getEcommerceSort')) {
            $sort = $this->getEcommerceSort();
            if (empty($sort)) {
                $sort = ['Title' => 'ASC'];
            }
            $list = is_array($sort) ? $list->sort($sort) : $list->orderBy($sort);
        }
        return $list;
    }

    /**
     * @return array
     */
    public function columns()
    {
        $array = [
            'InternalItemID' => [
                'title' => _t('EcommerceSideReport.PRODUCT_TYPE', 'Code'),
                'link' => true,
            ],
            'ProductType' => [
                'title' => _t('EcommerceSideReport.PRODUCT_TYPE', 'Type'),
                'link' => true,
            ],
            'ProductBreadcrumb' => [
                'title' => _t('EcommerceSideReport.BREADCRUMB', 'Breadcrumb'),
                'link' => true,
            ],
            'Title' => [
                'title' => _t('EcommerceSideReport.BUYABLE_NAME', 'Title'),
                'link' => true,
            ],
            'Price' => [
                'title' => _t('EcommerceSideReport.PRICE', 'Price'),
                'formatting' => function ($value, $item) {
                    return (intval($value) !== 0 ? DBField::create_field('Currency', $value)->Nice()  : 'n/a');
                },
                'csvFormatting' => function ($value, $item) {
                    return (intval($value) !== 0 ? DBField::create_field('Currency', $value)->Nice()  : 'n/a');
                },
            ],
        ];
        if ($this->hasMethod('updateEcommerceReportColumns')) {
            $array = $this->updateEcommerceReportColumns($array);
        }
        return $array;
    }

    public function parameterFields()
    {
        $fields = FieldList::create();
        $fields->push(
            FieldGroup::create(
                'Optional Filters',
                TextField::create(
                    'Title',
                    'Keyword',
                ),
                CurrencyField::create(
                    'MinimumPrice',
                    'Minimum Price',
                    0
                ),
                DropdownField::create(
                    'ForSale',
                    'For Sale',
                    [
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ]
                )
                    ->setEmptyString('-- Any --'),
                NumericField::create(
                    'ChangedInTheLastXDays',
                    'Changed less than ... days ago?',
                    ''
                ),
                NumericField::create(
                    'CreatedInTheLastXDays',
                    'Created less than ... days ago?',
                    ''
                ),
                DropdownField::create(
                    'ProductType',
                    'Product Type',
                    $this->getProductTypes()
                ),
                DropdownField::create(
                    'ParentID',
                    'Category',
                    $this->getProductGroups()
                )->setEmptyString('-- Any Category --'),
            )->addExtraClass('stacked')
        );
        $fields->recursiveWalk(
            function (FormField $field) {
                if (0 !== strpos($field->getName(), 'filter[')) {
                    $field->setName(sprintf('filters[%s]', $field->getName()));
                }

                $field->addExtraClass('no-change-track'); // ignore in changetracker
            }
        );

        if ($this->hasMethod('updateParameterFields')) {
            $this->updateParameterFields($fields);
        }

        return $fields;
    }

    protected function getProductTypes()
    {
        $list = ClassInfo::subClassesFor(Product::class, true);
        $newArray = [];
        foreach ($list as $className) {
            $newArray[$className] = Product::class === $className ? '-- Any Product --' : Injector::inst()->get($className)->i18n_plural_name();
        }

        return $newArray;
    }

    protected function getProductGroups(): array
    {
        $list = $this->sourceRecords();
        $array = [];
        if ($list->exists()) {
            $list = ProductGroup::get()
                ->filter([
                    'ID' => $list->columnUnique('ParentID'),
                    'ShowInSearch' => true,
                ]);
            $array = $list
                ->map('ID', 'FullName')
                ->toArray();
            $backupList = $list
                ->map('ID', 'Title')
                ->toArray();
            foreach ($array as $key => $value) {
                if (empty($value)) {
                    $array[$key] = $backupList[$key];
                }
            }
        }
        return asort($array, SORT_NATURAL | SORT_FLAG_CASE) ? $array : [];
    }

    // public function getReportField()
    // {
    //     $field = parent::getReportField();
    //     $config = $field->getConfig();
    //     $exportButton = $config->getComponentByType(GridFieldExportButton::class);
    //     $exportButton->setExportColumns($field->getColumns());
    //
    //     return $field;
    // }
}
