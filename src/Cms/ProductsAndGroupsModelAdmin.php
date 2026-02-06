<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldEditOriginalPageConfig;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;

/**
 * Class \Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin
 */
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
        if (is_subclass_of($this->modelClass, SiteTree::class) || SiteTree::class === $this->modelClass) {
            $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
            if ($gridField && $gridField instanceof GridField) {
                $config = GridFieldEditOriginalPageConfig::create();
                $exportButton = new GridFieldExportButton('buttons-before-left');
                $exportButton->setExportColumns($this->getExportFields());
                $config->addComponent($exportButton);
                $gridField->setConfig($config);
            }
        }

        return $form;
    }
}
