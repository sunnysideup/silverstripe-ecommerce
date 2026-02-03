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
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

trait EcommerceProductGroupReportTrait
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
        return 6500;
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
        $className = ($params['ProductGroupType'] ?? '');
        if ($className && class_exists($className)) {
            $list = $className::get()->filter(['ClassName' => $className]);
        } else {
            $className = $this->dataClass;
            $list = $className::get();
        }

        $title = (string) Convert::raw2sql($params['Title'] ?? '');
        if ($title) {
            $list = $list->filterAny(['Title:PartialMatch' => $title, 'AlternativeProductGroupNames:PartialMatch' => $title]);
        }

        $changedInTheLastXDays = (int) ($params['ChangedInTheLastXDays'] ?? 0);
        if ($changedInTheLastXDays) {
            $list = $list->where(['"LastEdited" >= DATE_ADD(CURDATE(), INTERVAL -' . (int) $changedInTheLastXDays . ' DAY)']);
        }

        $createdInTheLastXDays = (int) ($params['CreatedInTheLastXDays'] ?? 0);
        if ($createdInTheLastXDays) {
            $list = $list->where(['"Created" >= DATE_ADD(CURDATE(), INTERVAL -' . (int) $createdInTheLastXDays . ' DAY)']);
        }
        if ($this->hasMethod('getEcommerceFilter')) {
            $filter = $this->getEcommerceFilter();
            if (! empty($filter)) {
                $list = $list->filter($filter);
            }
        }
        $sort = null;
        if ($this->hasMethod('getEcommerceSort')) {
            $sort = $this->getEcommerceSort();
            if (empty($sort)) {
                $sort = ['Title' => 'ASC'];
            }
            if (is_array($sort)) {
                $list = $list->sort($sort);
            } else {
                $list = $list->orderBy($sort);
            }
        }

        if ($this->hasMethod('getEcommerceWhere')) {
            $where = $this->getEcommerceWhere();
            if (! empty($where)) {
                $list = $list->where($where);
            }
        }

        if ($this->hasMethod('updateEcommerceList')) {
            $list = $this->updateEcommerceList($list);
        }
        return $list;
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'ProductGroupType' => [
                'title' => _t('EcommerceSideReport.PRODUCT_GROUP_TYPE', 'Type'),
                'link' => true,
            ],
            'Breadcrumb' => [
                'title' => _t('EcommerceSideReport.BREADCRUMB', 'Breadcrumb'),
                'link' => true,
            ],
            'Title' => [
                'title' => _t('EcommerceSideReport.PRODUCT_GROUP_NAME', 'Category'),
                'link' => true,
            ],
        ];
    }

    public function parameterFields()
    {
        $fields = FieldList::create();
        $productTypes = $this->getProductGroupTypes();
        $fields->push(
            FieldGroup::create(
                'Optional Filters',
                TextField::create(
                    'Title',
                    'Keyword',
                ),
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
                    'ProductGroupType',
                    'Product Group Type',
                    $productTypes
                ),
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

        return $fields;
    }

    protected function getProductGroupTypes()
    {
        $list = ClassInfo::subClassesFor(ProductGroup::class, true);
        $newArray = [];
        foreach ($list as $className) {
            $newArray[$className] = ProductGroup::class === $className ? '(Any)' : Injector::inst()->get($className)->i18n_plural_name();
        }

        return $newArray;
    }
}
