<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Cms\CMSPageAddControllerProducts;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * Provides the entry point to editing a single record presented by the
 * {@link GridField}.
 *
 * Doesn't show an edit view on its own or modifies the record, but rather
 * relies on routing conventions established in {@link getColumnContent()}.
 *
 * The default routing applies to the {@link GridFieldDetailForm} component,
 * which has to be added separately to the {@link GridField} configuration.
 */
class GridFieldAddNewButtonOriginalPage extends GridFieldAddNewButton
{
    public function getHTMLFragments($gridField)
    {
        $singleton = singleton($gridField->getModelClass());

        if (! $singleton->canCreate()) {
            return [];
        }

        if (! $this->buttonName) {
            // provide a default button name, can be changed by calling {@link setButtonName()} on this component
            $objectName = $singleton->i18n_singular_name();
            $this->buttonName = _t('GridField.Add_USING_PAGES_SECTION', 'Add {name}', ['name' => $objectName]);
        }

        $getSegment = '';
        if ($page = $this->BestParentPage()) {
            $getSegment = '?ParentID=' . $page->ID;
        }

        $data = new ArrayData([
            'NewLink' => '/admin/' . Config::inst()->get(CMSPageAddControllerProducts::class, 'url_segment') . '/' . $getSegment,
            'ButtonName' => $this->buttonName,
        ]);

        $templates = SSViewer::get_templates_by_class($this, '', GridFieldAddNewButton::class);

        return [
            $this->targetFragment => $data->renderWith($templates),
        ];
    }

    /**
     * finds the most likely root parent for the shop.
     *
     * @return null|SiteTree
     */
    public function BestParentPage()
    {
        $defaultRootParentClass = Config::inst()->get(CMSPageAddControllerProducts::class, 'root_parent_class_for_adding_page');
        $rootParentClassArray = [$defaultRootParentClass, ProductGroup::class];
        foreach ($rootParentClassArray as $rootParentClass) {
            $result = DataObject::get_one(
                $rootParentClass,
                ['ParentID' => 0]
            );
            if ($result) {
                return $result;
            }
            $stage = '';
            if ('Live' === Versioned::get_stage()) {
                $stage = '_Live';
            }

            return $rootParentClass::get()
                ->filter('Parent.ParentID', 0)
                ->innerJoin(Config::inst()
                ->get(SiteTree::class, 'table_name') . $stage, 'Parent.ID = SiteTree' . $stage . '.ParentID', 'Parent')
                ->First()
            ;
        }
    }
}
