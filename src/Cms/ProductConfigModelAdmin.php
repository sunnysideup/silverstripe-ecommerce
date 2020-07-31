<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceSearchHistoryFormField;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Model\Search\SearchReplacement;

/**
 * @description: Manages stuff related to products,
 * but not the product (groups) themselves
 *
 * Main example is product variations
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/
class ProductConfigModelAdmin extends ModelAdmin
{
    use EcommerceModelAdminTrait;

    private static $menu_priority = 3.19;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'product-config';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Product Details';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        SearchReplacement::class,
        SearchHistory::class,
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $required_permission_codes = 'CMS_ACCESS_ProductConfigModelAdmin';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/product-file.gif';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm();
        if ($this->modelClass === SearchHistory::class) {
            if ($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
                $form->Fields()->replaceField(
                    $gridField->getName(),
                    EcommerceSearchHistoryFormField::create('SearchHistoryTable')
                        ->setShowMoreLink(true)
                );
            }
        }

        return $form;
    }
}
