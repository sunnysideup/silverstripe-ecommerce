<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;

trait EcommerceModelAdminTrait
{
    /**
     * @param DataObject $record
     *
     * @return Form
     */
    public function oneItemForm(DataObject $record)
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
