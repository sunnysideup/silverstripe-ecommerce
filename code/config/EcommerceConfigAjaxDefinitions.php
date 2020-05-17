<?php

/**
 * This class defines all the names for IDs and Classes that are used
 * within the e-commerce ajax framework.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration

 **/
class EcommerceConfigAjaxDefinitions extends ViewableData
{
    /**
     * the class that is requesting the ajax definitions
     * we provide the requestor so that we can dynamically change
     * the ids and classes, using the requestor.
     * e.g.
     * <code>
     * 	MyTableRowID(){
     * 		return $this->requestor->ClassName."_bla".$this->requestor->ID;
     * 	}
     * </code>.
     *
     * @var DataObject
     */
    protected $requestor = null;

    /**
     * prefix used for all classes and IDs.
     *
     * @var string|null
     */
    private static $prefix = null;

    /**
     * set the requestor.
     *
     * @param DataObject $do - the object that requested the data.
     */
    public function setRequestor($do)
    {
        if (self::$prefix === null) {
            self::$prefix = EcommerceConfig::get('Order', 'template_id_prefix');
        }
        $this->requestor = $do;
    }

    /*___________________

      0. without context
     ___________________*/

    /**
     * id that is used in templates and in the JSON return @see CartResponse
     * The Side bar cart ID is used for populating a small cart on the side bar.
     *
     * @see Sidebar_Cart.ss
     *
     * @return string
     **/
    public function SideBarCartID()
    {
        return self::$prefix . 'Side_Bar_Cart';
    }

    /**
     * Small representation of cart.
     *
     * @see CartShort.ss
     *
     * @return string
     **/
    public function SmallCartID()
    {
        return self::$prefix . 'small_cart_id';
    }

    /**
     * class that is used in templates and in the JSON return @see CartResponse
     * The Menu Cart class is used for populating a tiny cart on your site
     * (e.g. you have 3 items in your cart ($1343)).
     *
     * @see CartTiny.ss
     *
     * @return string
     **/
    public function TinyCartClassName()
    {
        return self::$prefix . 'tiny_cart_class';
    }

    /**
     * @return string
     **/
    public function HiddenPageTitleID()
    {
        return self::$prefix . 'HiddenPageTitle';
    }

    /**
     * @return string
     **/
    public function ProductListHolderID()
    {
        return self::$prefix . 'ProductGroup';
    }

    /**
     * @return string
     **/
    public function ProductListAjaxifiedLinkClassName()
    {
        return self::$prefix . 'ajaxifyMyProductGroupLinks';
    }

    /**
     * List of products in the ProductGroup page...
     *
     * @see ProductGroupItem.ss, ProductGroupItemMoreDetail.ss, and ProductGroupItemShort.ss
     *
     * @return string
     **/
    public function ProductListItemClassName()
    {
        return self::$prefix . 'productActions';
    }

    /**
     * Class used to identify that a product is in cart.
     *
     * @see ProductGroupItem.ss, ProductGroupItemMoreDetail.ss, and ProductGroupItemShort.ss
     *
     * @return string
     **/
    public function ProductListItemInCartClassName()
    {
        return self::$prefix . 'inCart';
    }

    /**
     * Class used to identify that a product is not in cart.
     *
     * @see ProductGroupItem.ss, ProductGroupItemMoreDetail.ss, and ProductGroupItemShort.ss
     *
     * @return string
     **/
    public function ProductListItemNotInCartClassName()
    {
        return self::$prefix . 'notInCart';
    }

    /*___________________

      1. Generic (Order / Modifier / OrderItem)
     ___________________*/

    /**
     *@return string for use in the Templates
     **/
    public function TableID()
    {
        return self::$prefix . $this->requestor->ClassName . '_DB_' . $this->requestor->ID;
    }

    /**
     *@return string for use in the Templates
     **/
    public function TableTotalID()
    {
        return $this->TableID() . '_Total';
    }

    /*___________________

      2. Order
     ___________________*/

    /**
     * id that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function TableMessageID()
    {
        return $this->TableID() . '_Message';
    }

    /**
     * id that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function TableSubTotalID()
    {
        return $this->TableID() . '_SubTotal';
    }

    /**
     * class that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function TotalItemsTimesQuantityClassName()
    {
        return self::$prefix . 'number_of_items_times_quantity';
    }

    /**
     * class that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function TotalItemsClassName()
    {
        return self::$prefix . 'number_of_items';
    }

    /**
     * class that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function ExpectedCountryClassName()
    {
        return self::$prefix . 'expected_country_selector';
    }

    /**
     * class that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function CountryFieldID()
    {
        return OrderAddress::get_country_field_ID();
    }

    /**
     * class that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function RegionFieldID()
    {
        return OrderAddress::get_region_field_ID();
    }

    /*___________________

      3. Order Attribute (Modifier + OrderItem)
     ___________________*/

    /**
     *@return string for use in the Templates
     **/
    public function TableTitleID()
    {
        return $this->TableID() . '_Title';
    }

    /**
     *@return string for use in the Templates
     **/
    public function CartTitleID()
    {
        return $this->TableID() . '_Title_Cart';
    }

    /**
     *@return string for use in the Templates
     **/
    public function TableSubTitleID()
    {
        return $this->TableID() . '_Sub_Title';
    }

    /**
     *@return string for use in the Templates
     **/
    public function CartSubTitleID()
    {
        return $this->TableID() . '_Sub_Title_Cart';
    }

    /*___________________

      4. OrderItems
     ___________________*/

    /**
     * id that is used in templates and in the JSON return @see CartResponse.
     *
     * @return string
     **/
    public function QuantityFieldName()
    {
        return $this->TableID() . '_Quantity_SetQuantityLink';
    }

    /*___________________

      5. Modifiers
     ___________________*/

    /*___________________

      6. Buyable
     ___________________*/

    /**
     * returns a string that can be used as a unique Identifier for use in templates, etc...
     *
     * @return string
     */
    public function UniqueIdentifier()
    {
        return $this->TableID() . '_Button';
    }

    public function Define($name)
    {
        $object = Injector::inst()->get('EcommerceConfigDefinitions');

        return $object->getAjaxMethod($name);
    }
}
