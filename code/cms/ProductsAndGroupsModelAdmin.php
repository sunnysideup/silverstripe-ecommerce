<?php


/**
 * @description: for the management of Product and Product Groups only
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/
class ProductsAndGroupsModelAdmin extends ModelAdminEcommerceBaseClass
{
    private static $menu_priority = 3.2;

    private static $url_segment = 'products';

    private static $menu_title = 'Products';

    private static $allowed_actions = [
        'editinsitetree',
        'ItemEditForm',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'ecommerce/images/icons/product-file.gif';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm();
        if (is_subclass_of($this->modelClass, 'SiteTree') || $this->modelClass === 'SiteTree') {
            if ($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
                if ($gridField instanceof GridField) {
                    $config = GridFieldEditOriginalPageConfig::create();
                    $exportButton = new GridFieldExportButton('buttons-before-left');
                    $exportButton->setExportColumns($this->getExportFields());
                    $config->addComponent($exportButton);
                    $gridField->setConfig($config);
                }
            }
        }

        return $form;
    }
}

