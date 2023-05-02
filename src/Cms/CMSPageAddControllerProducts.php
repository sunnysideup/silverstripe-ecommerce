<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\CMS\Controllers\CMSPageAddController;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\ArrayList;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;

/**
 * Class \Sunnysideup\Ecommerce\Cms\CMSPageAddControllerProducts
 *
 */
class CMSPageAddControllerProducts extends CMSPageAddController
{
    private static $url_segment = 'addproductorproductgroup';

    private static $url_rule = '$Action/$ID/$OtherID';

    private static $url_priority = 41;

    private static $menu_title = 'Add Product';

    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    private static $allowed_actions = [
        'AddForm',
        'doAdd',
        'doCancel',
    ];

    /**
     * the class of the page that is the root parent for the shop.
     *
     * @var string
     */
    private static $root_parent_class_for_adding_page = ProductGroupSearchPage::class;

    public function doCancel($data, $form)
    {
        return $this->redirect(singleton(ProductsAndGroupsModelAdmin::class)->Link());
    }

    /**
     * @return \SilverStripe\ORM\ArrayList
     */
    public function PageTypes()
    {
        $pageTypes = parent::PageTypes();
        $result = new ArrayList();

        $productClass = EcommerceConfigClassNames::getName(Product::class);
        $productGroupClass = EcommerceConfigClassNames::getName(ProductGroup::class);

        $acceptedClasses1 = ClassInfo::subclassesFor($productClass);
        $acceptedClasses1[$productClass] = $productClass;

        $acceptedClasses2 = ClassInfo::subclassesFor($productGroupClass);
        $acceptedClasses2[$productGroupClass] = $productGroupClass;

        $acceptedClasses = array_merge($acceptedClasses1, $acceptedClasses2);
        foreach ($pageTypes as $type) {
            if (in_array($type->ClassName, $acceptedClasses, true)) {
                $result->push($type);
            }
        }

        return $result;
    }
}
