<?php


class CMSPageAddController_Products extends CMSPageAddController
{
    private static $url_segment = 'product/add';
    private static $url_rule = '/$Action/$ID/$OtherID';
    private static $url_priority = 41;
    private static $menu_title = 'Add Product';
    private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

    private static $allowed_actions = array(
        'AddForm',
        'doAdd',
        'doCancel',
    );

    /**
     * the class of the page that is the root parent for the shop.
     *
     * @var string
     */
    private static $root_parent_class_for_adding_page = 'ProductGroupSearchPage';

    /**
     * @return Form
     */
    public function AddForm()
    {
        return parent::AddForm();
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
            if (in_array($type->ClassName, $acceptedClasses)) {
                $result->push($type);
            }
        }

        return $result;
    }
}
