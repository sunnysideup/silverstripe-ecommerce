<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldEditOriginalPageConfig;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * @description: for the management of Product and Product Groups only
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/
class ProductsAndGroupsModelAdmin extends ModelAdmin
{
    use EcommerceModelAdminTrait;

    private static $menu_priority = 3.2;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'products';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Products';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        Product::class,
        ProductGroup::class,
    ];

    private static $allowed_actions = [
        'editinsitetree',
        'ItemEditForm',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $required_permission_codes = 'CMS_ACCESS_ProductsAndGroupsModelAdmin';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/product-file.gif';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm();
        if (is_subclass_of($this->modelClass, SiteTree::class) || $this->modelClass === SiteTree::class) {
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
