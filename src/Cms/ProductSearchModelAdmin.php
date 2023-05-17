<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceSearchHistoryFormField;
use Sunnysideup\Ecommerce\Model\Search\ProductGroupSearchTable;
use Sunnysideup\Ecommerce\Model\Search\ProductSearchTable;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Model\Search\SearchReplacement;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;

/**
 * Class \Sunnysideup\Ecommerce\Cms\ProductSearchModelAdmin
 *
 */
class ProductSearchModelAdmin extends ModelAdmin
{
    use EcommerceModelAdminTrait;

    private static $menu_priority = 3.19;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'product-search';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Product Search';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        SearchReplacement::class,
        SearchHistory::class,
        ProductGroupSearchTable::class,
        ProductSearchTable::class,
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $required_permission_codes = 'CMS_ACCESS_ProductSearchModelAdmin';

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
