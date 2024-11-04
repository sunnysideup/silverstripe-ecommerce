<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceSearchHistoryFormField;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;

/**
 * Class \Sunnysideup\Ecommerce\Cms\ProductConfigModelAdmin
 *
 */
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
    private static $menu_title = 'Product Categories';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        ProductGroup::class,
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
        if (SearchHistory::class === $this->modelClass) {
            $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
            if ($gridField) {
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
