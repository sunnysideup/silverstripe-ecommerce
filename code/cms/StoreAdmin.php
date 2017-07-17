<?php


/**
 * @description: CMS management for the store setup (e.g Order Steps, Countries, etc...)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
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
     * @var int
     */
    private static $menu_priority = 3.3;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'ecommerce/images/icons/cart-file.gif';

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
        $ecommerceDBConfig = isset($models['EcommerceDBConfig']) ? $models['EcommerceDBConfig'] : null;
        if ($ecommerceDBConfig) {
            unset($models['EcommerceDBConfig']);

            return array('EcommerceDBConfig' => $ecommerceDBConfig) + $models;
        }

        return $models;
    }


    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if (is_subclass_of($this->modelClass, 'EcommerceDBConfig') || $this->modelClass === 'EcommerceDBConfig') {
            $record = DataObject::get_one('EcommerceDBConfig');
            if($record && $record->exists()) {
                return $this->oneItemForm($record);
            }
            if ($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
                if ($gridField instanceof GridField) {
                    $config = $gridField->getConfig();
                    $config->removeComponentsByType('GridFieldExportButton');
                    $config->removeComponentsByType('GridFieldPrintButton');
                }
            }
        }

        return $form;
    }


}
