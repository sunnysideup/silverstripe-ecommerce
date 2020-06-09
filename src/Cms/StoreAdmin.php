<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;

/**
 * @description: CMS management for the store setup (e.g Order Steps, Countries, etc...)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms

 **/
class StoreAdmin extends ModelAdminEcommerceBaseClass
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'shop';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Shop Settings';

    /**
     * standard SS variable.
     *
     * @var float
     */
    private static $menu_priority = 3.3;

    /**
     * standard SS variable.
     *
     * @var string
     */
    /* TODO UPGRADE: fix the following line */
    //private static $menu_icon = 'ecommerce/client/images/icons/cart-file.gif';

    public function init()
    {
        parent::init();
    }

    /**
     *@return string (URLSegment)
     **/
    public function urlSegmenter()
    {
        return $this->config()->get('url_segment');
    }

    /**
     * @return array Map of class name to an array of 'title' (see {@link $managed_models})
     *               we make sure that the EcommerceDBConfig is FIRST
     */
    public function getManagedModels()
    {
        $models = parent::getManagedModels();
        $ecommerceDBConfig = isset($models[EcommerceDBConfig::class]) ? $models[EcommerceDBConfig::class] : null;
        if ($ecommerceDBConfig) {
            unset($models[EcommerceDBConfig::class]);

            return [EcommerceDBConfig::class => $ecommerceDBConfig] + $models;
        }

        return $models;
    }

    /**
     * @param int $id
     * @param int $fields SilverStripe\Forms\FieldList
     *
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if ($this->modelClass === EcommerceDBConfig::class || is_subclass_of($this->modelClass, EcommerceDBConfig::class)) {
            $record = DataObject::get_one(EcommerceDBConfig::class);
            if ($record && $record->exists()) {
                return $this->oneItemForm($record);
            }
            if ($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
                if ($gridField instanceof GridField) {
                    $config = $gridField->getConfig();
                    $config->removeComponentsByType(GridFieldExportButton::class);
                    $config->removeComponentsByType(GridFieldPrintButton::class);
                }
            }
        }

        return $form;
    }
}
