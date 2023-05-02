<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;

/**
 * Class \Sunnysideup\Ecommerce\Cms\StoreAdmin
 *
 */
class StoreAdmin extends ModelAdmin
{
    use EcommerceModelAdminTrait;

    private static $shortcuts = [];

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
     * @var array
     */
    private static $managed_models = [
        EcommerceDBConfig::class,
        OrderStep::class,
        EcommerceCountry::class,
        EcommerceCurrency::class,
    ];

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
    private static $required_permission_codes = 'CMS_ACCESS_ShopAdmin';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/cart-file.gif';

    /**
     * @return string (URLSegment)
     */
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
        if (singleton($this->modelClass) instanceof EcommerceDBConfig) {
            $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
            if ($gridField) {
                if ($gridField instanceof GridField) {
                    $config = $gridField->getConfig();
                    $config->removeComponentsByType(GridFieldExportButton::class);
                    $config->removeComponentsByType(GridFieldPrintButton::class);
                    $config->removeComponentsByType(GridFieldImportButton::class);
                    $config->removeComponentsByType(GridFieldFilterHeader::class);
                    $config->removeComponentsByType(GridFieldSortableHeader::class);
                }
            }
        }

        return $form;
    }
}
