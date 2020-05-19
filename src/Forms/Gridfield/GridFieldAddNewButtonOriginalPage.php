<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;

use GridFieldAddNewButton;
use ArrayData;
use Config;
use DataObject;
use Versioned;


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
            'NewLink' => '/admin/' . Config::inst()->get('CMSPageAddControllerProducts', 'url_segment') . '/' . $getSegment,
            'ButtonName' => $this->buttonName,
        ]);

        return [

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: ->RenderWith( (ignore case)
  * NEW: ->RenderWith( (COMPLEX)
  * EXP: Check that the template location is still valid!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $this->targetFragment => $data->RenderWith('GridFieldAddNewbutton'),
        ];
    }

    /**
     * finds the most likely root parent for the shop.
     *
     * @return SiteTree | NULL
     */
    public function BestParentPage()
    {
        $defaultRootParentClass = Config::inst()->get('CMSPageAddControllerProducts', 'root_parent_class_for_adding_page');
        $rootParentClassArray = [$defaultRootParentClass, 'ProductGroup'];
        foreach ($rootParentClassArray as $rootParentClass) {
            $result = DataObject::get_one(
                $rootParentClass,
                ['ParentID' => 0]
            );
            if ($result) {
                return $result;
            }
            $stage = '';
            if (Versioned::current_stage() === 'Live') {
                $stage = '_Live';
            }
            if ($result = $rootParentClass::get()->filter('MyParentPage.ParentID', 0)->innerJoin('SiteTree' . $stage, 'MyParentPage.ID = SiteTree' . $stage . '.ParentID', 'MyParentPage')->First()) {
                return $result;
            }
        }
    }
}

