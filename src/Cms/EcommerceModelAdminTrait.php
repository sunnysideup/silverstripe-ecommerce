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
