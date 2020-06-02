<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;

/**
 * @see: http://doc.silverstripe.org/framework/en/reference/ModelAdmin
 *
 * @author Nicolaas [at] sunnyside up . co .nz
 */
class ModelAdminEcommerceBaseClass extends ModelAdmin
{
    private static $url_segment = 'ecommerce-base';
    /**
     * Change this variable if you don't want the Import from CSV form to appear.
     * This variable can be a boolean or an array.
     * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo').
     */
    public $showImportForm = false;

    /**
     * @return array Map of class name to an array of 'title' (see {@link $managed_models})
     */
    public function getManagedModels()
    {
        if ($this->ClassName === ModelAdminEcommerceBaseClass::class) {
            //never used
            return ['NothingGoesHere' => ['title' => 'All Orders']];
        }
        return parent::getManagedModels();
    }

    /**
     * @param DataObject $record
     *
     * @return Form
     */
    public function oneItemForm($record)
    {
        Config::modify()->update(LeftAndMain::class, 'tree_class', $record->ClassName);
        $form = LeftAndMain::getEditForm($record);
        $idField = HiddenField::create('ID')->setValue($record->ID);
        $cssField = LiteralField::create(
            'oneItemFormCSS',
            '
                <style>
                    .cms-content-view .ui-tabs-nav {
                        margin-left: 0!important;
                    }
                    .cms-content-view .Actions {
                        position: fixed;
                        bottom: 16px;
                        right:  16px;
                    }
                </style>
            '
        );
        $form->Fields()->push($idField);
        $form->Fields()->push($cssField);
        return $form;
    }

    /**
     * Define which fields are used in the {@link getEditForm} GridField export.
     * By default, it uses the summary fields from the model definition.
     *
     * @return array
     */
    public function getExportFields()
    {
        $obj = Injector::inst()->get($this->modelClass);
        if ($obj->hasMethod('getExportFields')) {
            return $obj->getExportFields();
        }
        return $obj->summaryFields();
    }
}
