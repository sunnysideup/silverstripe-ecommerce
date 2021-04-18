<?php

namespace Sunnysideup\Ecommerce\Traits;

use SilverStripe\Core\Injector\Injector;

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
