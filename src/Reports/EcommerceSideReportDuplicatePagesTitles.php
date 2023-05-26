<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Reports\Report;

/**
 * EcommerceSideReport classes are to allow quick reports that can be accessed
 * on the Reports tab to the left inside the SilverStripe CMS.
 * Currently there are reports to show products flagged as 'FeatuedProduct',
 * as well as a report on all products within the system.
 */

/**
 * Ecommerce Pages except Products.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportDuplicatePages extends Report
{
    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = SiteTree::class;

    private static $excluded_class_names = [];

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 5999;
    }

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.DUPLICATE_PAGES', 'Pages with duplicate names');
    }

    /**
     * not sure if this is used in SS3.
     *
     * @return string
     */
    public function group()
    {
        return _t('EcommerceSideReport.PAGES', 'Pages');
    }

    /**
     * working out the items.
     *
     * @param null|mixed $params
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function sourceRecords($params = null)
    {
        $list = SiteTree::get()
            ->where('
                (TheOtherSiteTree.ID IS NOT NULL AND TheOtherSiteTree.ID <> SiteTree.ID)
            ')
            ->sort('Title', 'ASC')
            ->leftJoin(
                'SiteTree',
                '"SiteTree"."Title" = TheOtherSiteTree.Title',
                'TheOtherSiteTree'
            )
        ;
        $classNameFilter = $params['ClassNameFilter'] ?? '';
        if ($classNameFilter && class_exists($classNameFilter)) {
            $list = $list->filter(['ClassName' => $classNameFilter]);
        }
        $array = $this->Config()->get('excluded_class_names');
        if (count($array)) {
            $list = $list->exclude(['ClassName' => $array]);
        }

        return $list;
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'Title' => [
                'title' => _t('EcommerceSideReport.Title', 'Title'),
                'link' => true,
            ],
            'Link' => [
                'title' => _t('EcommerceSideReport.Link', 'Link'),
                'link' => true,
            ],
        ];
    }

    public function parameterFields()
    {
        $params = FieldList::create();
        $list = ClassInfo::subClassesFor(SiteTree::class, false);
        $array = [];
        foreach ($list as $className) {
            $obj = Injector::inst()->get($className);
            $array[$className] = $obj->i18n_singular_name();
        }
        asort($array);
        $params->push(
            DropdownField::create(
                'ClassNameFilter',
                'Page Type',
                $array
            )
                ->setEmptyString('--- Any ---')
        );

        return $params;
    }
}
