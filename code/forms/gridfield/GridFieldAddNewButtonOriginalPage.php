<?php

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

        if (!$singleton->canCreate()) {
            return array();
        }

        if (!$this->buttonName) {
            // provide a default button name, can be changed by calling {@link setButtonName()} on this component
            $objectName = $singleton->i18n_singular_name();
            $this->buttonName = _t('GridField.Add_USING_PAGES_SECTION', 'Add {name}', array('name' => $objectName));
        }

        $getSegment = '';
        if ($page = $this->BestParentPage()) {
            $getSegment = '?ParentID='.$page->ID;
        }

        $data = new ArrayData(array(
            'NewLink' => '/admin/'.Config::inst()->get('CMSPageAddController_Products', 'url_segment').'/'.$getSegment,
            'ButtonName' => $this->buttonName,
        ));

        return array(
            $this->targetFragment => $data->renderWith('GridFieldAddNewbutton'),
        );
    }

    /**
     * finds the most likely root parent for the shop.
     *
     * @return SiteTree | NULL
     */
    public function BestParentPage()
    {
        $defaultRootParentClass = Config::inst()->get('CMSPageAddController_Products', 'root_parent_class_for_adding_page');
        $rootParentClassArray = array($defaultRootParentClass, 'ProductGroup');
        foreach ($rootParentClassArray as $rootParentClass) {
            if ($result = DataObject::get_one($rootParentClass, array('ParentID' => 0))) {
                return $result;
            }
            $stage = '';
            if (Versioned::current_stage() == 'Live') {
                $stage = '_Live';
            }
            if ($result = $rootParentClass::get()->filter('MyParentPage.ParentID', 0)->innerJoin('SiteTree'.$stage, 'MyParentPage.ID = SiteTree'.$stage.'.ParentID', 'MyParentPage')->First()) {
                return $result;
            }
        }
    }
}
