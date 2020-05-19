<?php


class CMSPageAddControllerProducts extends CMSPageAddController
{
    private static $url_segment = 'addproductorproductgroup';

    private static $url_rule = '/$Action/$ID/$OtherID';

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
    private static $root_parent_class_for_adding_page = 'ProductGroupSearchPage';

    public function doCancel($data, $form)
    {
        return $this->redirect(singleton('ProductsAndGroupsModelAdmin')->Link());
    }

    /**
     * @return ArrayList
     */
    public function PageTypes()
    {
        $pageTypes = parent::PageTypes();
        $result = new ArrayList();
        $productClass = Object::getCustomClass('Product');
        $productGroupClass = Object::getCustomClass('ProductGroup');

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

