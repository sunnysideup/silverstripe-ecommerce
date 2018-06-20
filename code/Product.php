<?php
/**
 * This is a standard Product page-type with fields like
 * Price, Weight, Model and basic management of
 * groups.
 *
 * It also has an associated Product_OrderItem class,
 * an extension of OrderItem, which is the mechanism
 * that links this page type class to the rest of the
 * eCommerce platform. This means you can add an instance
 * of this page type to the shopping cart.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: buyables
 * @inspiration: Silverstripe Ltd, Jeremy
 * @todo: Ask the silverstripe gods why $default_sort won't work with FullSiteTreeSort
 **/
class Product extends Page implements BuyableModel
{
    /**
     * Standard SS variable.
     */
    private static $api_access = array(
        'view' => array(
            'Title',
            'Price',
            'Weight',
            'Model',
            'Quantifier',
            'FeaturedProduct',
            'AllowPurchase',
            'InternalItemID', //ie SKU, ProductID etc (internal / existing recognition of product)
            'NumberSold', //store number sold, so it doesn't have to be computed on the fly. Used for determining popularity.
            'Version',
        ),
    );

    /**
     * Standard SS variable.
     */
    private static $db = array(
        'Price' => 'Currency',
        'Weight' => 'Float',
        'Model' => 'Varchar(30)',
        'Quantifier' => 'Varchar(30)',
        'FeaturedProduct' => 'Boolean',
        'AllowPurchase' => 'Boolean',
        'InternalItemID' => 'Varchar(30)', //ie SKU, ProductID etc (internal / existing recognition of product)
        'NumberSold' => 'Int', //store number sold, so it doesn't have to be computed on the fly. Used for determining popularity.
        'FullSiteTreeSort' => 'Decimal(64, 0)', //store the complete sort numbers from current page up to level 1 page, for sitetree sorting
        'FullName' => 'Varchar(255)', //Name for look-up lists
        'ShortDescription' => 'Varchar(255)', //For use in lists.
    );

    /**
     * Standard SS variable.
     */
    private static $has_one = array(
        'Image' => 'Product_Image',
    );

    /**
     * Standard SS variable.
     */
    private static $many_many = array(
        'ProductGroups' => 'ProductGroup',
        'AdditionalImages' => 'Image',
        'AdditionalFiles' => 'File',
    );

    /**
     * Standard SS variable.
     */
    private static $casting = array(
        'CalculatedPrice' => 'Currency',
        'CalculatedPriceAsMoney' => 'Money',
        'AllowPurchaseNice' => 'Varchar',
    );

    /**
     * Standard SS variable.
     */
    private static $indexes = array(
        'FullSiteTreeSort' => true,
        'FullName' => true,
        'InternalItemID' => true,
    );

    /**
     * Standard SS variable.
     */
    private static $defaults = array(
        'AllowPurchase' => 1,
    );

    /**
     * Standard SS variable.
     */
    //private static $default_sort = "\"FullSiteTreeSort\" ASC, \"Sort\" ASC, \"InternalItemID\" ASC, \"Price\" ASC";
    //private static $default_sort = "\"Sort\" ASC, \"InternalItemID\" ASC, \"Price\" ASC";

    /**
     * Standard SS variable.
     */
    private static $summary_fields = array(
        'Image.CMSThumbnail' => 'Image',
        'FullName' => 'Description',
        'Price' => 'Price',
        'AllowPurchaseNice' => 'For Sale',
    );

    /**
     * Standard SS variable.
     */
    private static $searchable_fields = array(
        'FullName' => array(
            'title' => 'Keyword',
            'field' => 'TextField',
        ),
        'Price' => array(
            'title' => 'Price',
            'field' => 'NumericField',
        ),
        'InternalItemID' => array(
            'title' => 'Internal Item ID',
            'filter' => 'PartialMatchFilter',
        ),
        'AllowPurchase',
        'ShowInSearch',
        'ShowInMenus',
        'FeaturedProduct',
    );

    /**
     * By default we search for products that are allowed to be purchased only
     * standard SS method.
     *
     * @return FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields->fieldByName('AllowPurchase')->setValue(1);

        return $fields;
    }

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Product';
    public function i18n_singular_name()
    {
        return _t('Order.PRODUCT', 'Product');
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Products';
    public function i18n_plural_name()
    {
        return _t('Order.PRODUCTS', 'Products');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A product that is for sale in the shop.';

    /**
     * Standard SS variable.
     */
    private static $default_parent = 'ProductGroup';

    /**
     * Standard SS variable.
     */
    private static $icon = 'ecommerce/images/icons/product';

    /**
     * Standard SS Method.
     */
    public function getCMSFields()
    {
        //prevent calling updateSettingsFields extend function too early
        //$siteTreeFieldExtensions = $this->get_static('SiteTree','runCMSFieldsExtensions');
        //$this->disableCMSFieldsExtensions();
        $fields = parent::getCMSFields();
        if ($this->Config()->get('add_data_to_meta_description_for_search')) {
            $fields->removeByName('MetaDescription');
        }
        //if($siteTreeFieldExtensions) {
        //$this->enableCMSFieldsExtensions();
        //}
        $fields->replaceField('Root.Main', $htmlEditorField = new HTMLEditorField('Content', _t('Product.DESCRIPTION', 'Product Description')));
        $htmlEditorField->setRows(3);
        $fields->addFieldToTab('Root.Main', new TextField('ShortDescription', _t('Product.SHORT_DESCRIPTION', 'Short Description')), 'Content');
        //dirty hack to show images!
        $fields->addFieldToTab('Root.Images', $uploadField = new Product_ProductImageUploadField('Image', _t('Product.IMAGE', 'Product Image')));
        $uploadField->setCallingClass('Product');
        $fields->addFieldToTab('Root.Images', $this->getAdditionalImagesField());
        $fields->addFieldToTab('Root.Images', $this->getAdditionalImagesMessage());
        $fields->addFieldToTab('Root.Images', $this->getAdditionalFilesField());
        $fields->addFieldToTab('Root.Details', new ReadonlyField('FullName', _t('Product.FULLNAME', 'Full Name')));
        $fields->addFieldToTab('Root.Details', new ReadOnlyField('FullSiteTreeSort', _t('Product.FULLSITETREESORT', 'Full sort index')));
        $fields->addFieldToTab('Root.Details', $allowPurchaseField = new CheckboxField('AllowPurchase', _t('Product.ALLOWPURCHASE', 'Allow product to be purchased')));
        $config = $this->EcomConfig();
        if ($config && !$config->AllowFreeProductPurchase) {
            $price = $this->getCalculatedPrice();
            if ($price == 0) {
                $link = $config->CMSEditLink();
                $allowPurchaseField->setDescription(
                    _t(
                        'Product.DO_NOT_ALLOW_FREE_PRODUCTS_TO_BE_PURCHASED',
                        "NB: Allow Purchase + zero price is not allowed.  Change the <a href=\"$link\">Shop Settings</a> to allow a zero price product purchases or set price on this product."
                    )
                );
            }
        }

        $fields->addFieldToTab('Root.Details', new CheckboxField('FeaturedProduct', _t('Product.FEATURED', 'Featured Product')));
        $fields->addFieldToTab('Root.Details', new NumericField('Price', _t('Product.PRICE', 'Price'), '', 12));
        $fields->addFieldToTab('Root.Details', new TextField('InternalItemID', _t('Product.CODE', 'Product Code'), '', 30));
        if ($this->EcomConfig()->ProductsHaveWeight) {
            $fields->addFieldToTab('Root.Details', new NumericField('Weight', _t('Product.WEIGHT', 'Weight')));
        }
        if ($this->EcomConfig()->ProductsHaveModelNames) {
            $fields->addFieldToTab('Root.Details', new TextField('Model', _t('Product.MODEL', 'Model')));
        }
        if ($this->EcomConfig()->ProductsHaveQuantifiers) {
            $fields->addFieldToTab(
                'Root.Details',
                TextField::create('Quantifier', _t('Product.QUANTIFIER', 'Quantifier'))
                    ->setRightTitle(_t('Product.QUANTIFIER_EXPLANATION', 'e.g. per kilo, per month, per dozen, each'))
            );
        }
        if ($this->canPurchase()) {
            $fields->addFieldToTab(
                'Root.Main',
                new LiteralField(
                    'AddToCartLink',
                    '<p class="message good"><a href="'.$this->AddLink().'">'._t('Product.ADD_TO_CART', 'add to cart').'</a></p>'
                )
            );
        } else {
            $fields->addFieldToTab(
                'Root.Main',
                new LiteralField(
                    'AddToCartLink',
                    '<p class="message warning">'._t('Product.CAN_NOT_BE_ADDED_TO_CART', 'this product can not be added to cart').'</p>'
                )
            );
        }
        if ($this->EcomConfig()->ProductsAlsoInOtherGroups) {
            $fields->addFieldsToTab(
                'Root.AlsoShowHere',
                array(
                    new HeaderField('ProductGroupsHeader', _t('Product.ALSOSHOWSIN', 'Also shows in ...')),
                    $this->getProductGroupsTableField(),
                )
            );
        }
        //if($siteTreeFieldExtensions) {
        //$this->extend('updateSettingsFields', $fields);
        //}
        return $fields;
    }

    /**
     * Used in getCSMFields.
     *
     * @return GridField
     **/
    protected function getProductGroupsTableField()
    {
        $gridField = new GridField(
            'ProductGroups',
            _t('Product.THIS_PRODUCT_SHOULD_ALSO_BE_LISTED_UNDER', 'This product is also listed under ...'),
            $this->ProductGroups(),
            GridFieldBasicPageRelationConfig::create()
        );

        return $gridField;
    }

    /**
     * Used in getCSMFields.
     *
     * @return LiteralField
     **/
    protected function getAdditionalImagesMessage()
    {
        $msg = '';
        if ($this->InternalItemID) {
            $findImagesTask = EcommerceTaskLinkProductWithImages::create();
            $findImagesLink = $findImagesTask->Link();
            $findImagesLinkOne = $findImagesLink.'?productid='.$this->ID;
            $msg .= '
                <h3>Batch Upload</h3>
                <p>
                To batch upload additional images and files, please go to the <a href="/admin/assets">Files section</a>, and upload them there.
                Files need to be named in the following way:
                An additional image for your product should be named &lt;Product Code&gt;_(00 to 99).(png/jpg/gif). <br />For example, you may name your image:
                <strong>'.$this->InternalItemID."_08.jpg</strong>.
                <br /><br />You can <a href=\"$findImagesLinkOne\" target='_blank'>find images for <i>".$this->Title."</i></a> or
                <a href=\"$findImagesLink\" target='_blank'>images for all products</a> ...
            </p>";
        } else {
            $msg .= '
            <h3>Batch Upload</h3>
            <p>To batch upload additional images and files, you must first specify a product code.</p>';
        }
        $field = new LiteralField('ImageFileNote', $msg);

        return $field;
    }

    /**
     * Used in getCSMFields.
     *
     * @return GridField
     **/
    protected function getAdditionalImagesField()
    {
        $uploadField = new UploadFIeld(
            'AdditionalImages',
            'More images'
        );
        $uploadField->setAllowedMaxFileNumber(12);
        return $uploadField;
    }

    /**
     * Used in getCSMFields.
     *
     * @return GridField
     **/
    protected function getAdditionalFilesField()
    {
        $uploadField = new UploadFIeld(
            'AdditionalFiles',
            'Additional Files'
        );
        $uploadField->setAllowedMaxFileNumber(12);
        return $uploadField;
    }

    /**
     * How to view using AJAX
     * e.g. if you want to load the produyct in a list - using AJAX
     * then use this link
     * Opening the link will return a HTML snippet.
     *
     * @return string
     */
    public function AjaxLink()
    {
        return $this->Link('ajaxview');
    }

    /**
     * Adds keywords to the MetaKeyword
     * Standard SS Method.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $config = $this->EcomConfig();
        //set allowpurchase to false IF
        //free products are not allowed to be purchased

        $filter = EcommerceCodeFilter::create();
        $filter->checkCode($this, 'InternalItemID');
        $this->prepareFullFields();
        //we are adding all the fields to the keyword fields here for searching purposes.
        //because the MetaKeywords Field is being searched.
        if ($this->Config()->get('add_data_to_meta_description_for_search')) {
            $this->MetaDescription = '';
            $fieldsToExclude = Config::inst()->get('SiteTree', 'db');
            foreach ($this->db() as $fieldName => $fieldType) {
                if (is_string($this->$fieldName) && strlen($this->$fieldName) > 2) {
                    if (!in_array($fieldName, $fieldsToExclude)) {
                        $this->MetaDescription .= strip_tags($this->$fieldName);
                    }
                }
            }
            if ($this->hasExtension('ProductWithVariationDecorator')) {
                $variations = $this->Variations();
                if ($variations) {
                    $variationCount = $variations->count();
                    if ($variationCount > 0 && $variationCount < 8) {
                        foreach ($variations as $variation) {
                            $this->MetaDescription .= ' - '.$variation->FullName;
                        }
                    }
                }
            }
        }
    }

    /**
     * standard SS Method
     * Make sure that the image is a product image.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->ImageID) {
            if ($normalImage = Image::get()->exclude(array('ClassName' => 'Product_Image'))->byID($this->ImageID)) {
                $normalImage = $normalImage->newClassInstance('Product_Image');
                $normalImage->write();
            }
        }
    }


    /**
     * sets the FullName and FullSiteTreeField to the latest values
     * This can be useful as you can compare it to the ones saved in the database.
     * Returns true if the value is different from the one in the database.
     *
     * @return bool
     */
    public function prepareFullFields()
    {
        //FullName
        $fullName = '';
        if ($this->InternalItemID) {
            $fullName .= $this->InternalItemID.': ';
        }
        $fullName .= $this->Title;
        //FullSiteTreeSort
        $parentSortArray = array(sprintf('%03d', $this->Sort));
        $obj = $this;
        $parentTitleArray = array();
        while ($obj && $obj->ParentID) {
            $obj = SiteTree::get()->byID(intval($obj->ParentID) - 0);
            if ($obj) {
                $parentSortArray[] = sprintf('%03d', $obj->Sort);
                if (is_a($obj, Object::getCustomClass('ProductGroup'))) {
                    $parentTitleArray[] = $obj->Title;
                }
            }
        }
        $reverseArray = array_reverse($parentSortArray);
        $parentTitle = '';
        if (count($parentTitleArray)) {
            $parentTitle = ' ('._t('product.IN', 'in').' '.implode(' / ', $parentTitleArray).')';
        }
        //setting fields with new values!
        $this->FullName = $fullName.$parentTitle;
        $this->FullSiteTreeSort = implode('', array_map($this->numberPad, $reverseArray));
        if (($this->dbObject('FullName') != $this->FullName) || ($this->dbObject('FullSiteTreeSort') != $this->FullSiteTreeSort)) {
            return true;
        }

        return false;
    }

    //GROUPS AND SIBLINGS

    /**
     * Returns all the parent groups for the product.
     *
     *@return DataList (ProductGroups)
     **/
    public function AllParentGroups()
    {
        $otherGroupsArray = $this->ProductGroups()->map('ID', 'ID')->toArray();

        return ProductGroup::get()->filter(
            array(
                'ID' => array($this->ParentID => $this->ParentID) + $otherGroupsArray,
            )
        );
    }

    /**
     * Returns all the parent groups for the product,
     * including the parents and parents and so on.
     *
     * @return DataList (ProductGroups)
     */
    public function AllParentGroupsIncludingParents()
    {
        $directParents = $this->AllParentGroups();
        $allParentsArray = array();
        foreach ($directParents as $parent) {
            $obj = $parent;
            $allParentsArray[$obj->ID] = $obj->ID;
            while ($obj && $obj->ParentID) {
                $obj = SiteTree::get()->byID(intval($obj->ParentID) - 0);
                if ($obj) {
                    if (is_a($obj, Object::getCustomClass('ProductGroup'))) {
                        $allParentsArray[$obj->ID] = $obj->ID;
                    }
                }
            }
        }

        return ProductGroup::get()->filter(array('ID' => $allParentsArray));
    }

    /**
     * @return Product ...
     * we have this so that Variations can link to products
     * and products link to themselves...
     */
    public function getProduct()
    {
        return $this;
    }

    /**
     * Returns the direct parent group for the product.
     *
     * @return ProductGroup | NULL
     **/
    public function MainParentGroup()
    {
        return ProductGroup::get()->byID($this->ParentID);
    }

    /**
     * Returns the top parent group of the product (in the hierarchy).
     *
     * @return ProductGroup | NULL
     **/
    public function TopParentGroup()
    {
        $parent = $this->MainParentGroup();
        $x = 0;
        while ($parent && $x < 100) {
            $returnValue = $parent;
            $parent = DataObject::get_one(
                'ProductGroup',
                array('ID' => $parent->ParentID)
            );
            ++$x;
        }

        return $returnValue;
    }

    /**
     * Returns products in the same group.
     *
     * @return DataList (Products)
     **/
    public function Siblings()
    {
        if ($this->ParentID) {
            $extension = '';
            if (Versioned::current_stage() == 'Live') {
                $extension = '_Live';
            }

            return Product::get()
                ->filter(array(
                    'ShowInMenus' => 1,
                    'ParentID' => $this->ParentID,
                ))
                ->exclude(array('ID' => $this->ID));
        }
    }

    //IMAGE
    /**
     * returns a "BestAvailable" image if the current one is not available
     * In some cases this is appropriate and in some cases this is not.
     * For example, consider the following setup
     * - product A with three variations
     * - Product A has an image, but the variations have no images
     * With this scenario, you want to show ONLY the product image
     * on the product page, but if one of the variations is added to the
     * cart, then you want to show the product image.
     * This can be achieved bu using the BestAvailable image.
     *
     * @return Image | Null
     */
    public function BestAvailableImage()
    {
        $product = Product::get()->byID($this->ID);
        if ($product && $product->ImageID) {
            $image = Image::get()->byID($product->ImageID);
            if ($image) {
                if (file_exists($image->getFullPath())) {
                    return $image;
                }
            }
        }
        if ($parent = $this->MainParentGroup()) {
            return $parent->BestAvailableImage();
        }
    }

    /**
     * Little hack to show thumbnail in summary fields in modeladmin in CMS.
     *
     * @return string (HTML = formatted image)
     */
    public function CMSThumbnail()
    {
        if ($image = $this->Image()) {
            if ($image->exists()) {
                return $image->Thumbnail();
            }
        }

        return '['._t('product.NOIMAGE', 'no image').']';
    }

    /**
     * Returns a link to a default image.
     * If a default image is set in the site config then this link is returned
     * Otherwise, a standard link is returned.
     *
     * @return string
     */
    public function DefaultImageLink()
    {
        return $this->EcomConfig()->DefaultImageLink();
    }

    /**
     * returns the default image of the product.
     *
     * @return Image | Null
     */
    public function DefaultImage()
    {
        return $this->EcomConfig()->DefaultImage();
    }

    /**
     * returns a product image for use in templates
     * e.g. $DummyImage.Width();.
     *
     * @return Product_Image
     */
    public function DummyImage()
    {
        return new Product_Image();
    }

    // VERSIONING

    /**
     * Conditions for whether a product can be purchased.
     *
     * If it has the checkbox for 'Allow this product to be purchased',
     * as well as having a price, it can be purchased. Otherwise a user
     * can't buy it.
     *
     * Other conditions may be added by decorating with the canPurcahse function
     *
     * @return bool
     */

    /**
     * @TODO: complete
     *
     * @param string $compontent - the has many relationship you are looking at, e.g. OrderAttribute
     *
     * @return DataList (CHECK!)
     */
    public function getVersionedComponents($component = 'ProductVariations')
    {
        return;
        $baseTable = ClassInfo::baseDataClass(self::$has_many[$component]);
        $query = singleton(self::$has_many[$component])->buildVersionSQL("\"{$baseTable}\".ProductID = {$this->ID} AND \"{$baseTable}\".Version = {$this->Version}");
        $result = singleton(self::$has_many[$component])->buildDataObjectSet($query->execute());

        return $result;
    }

    /**
     * Action to return specific version of a specific product.
     * This can be any product to enable the retrieval of deleted products.
     * This is really useful for sold products where you want to retrieve the actual version that you sold.
     * If the version can not be found then we retrieve the current one.
     *
     * @param int $id
     * @param int $version
     *
     * @return DataObject | Null
     */
    public function getVersionOfBuyable($id = 0, $version = 0)
    {
        if (!$id) {
            $id = $this->ID;
        }
        if (!$version) {
            $version = $this->Version;
        }
        //not sure why this is running via OrderItem...
        $obj = OrderItem::get_version($this->ClassName, $id, $version);
        if (!$obj) {
            $className = $this->ClassName;
            $obj = $className::get()->byID($id);
        }

        return $obj;
    }

    //ORDER ITEM

    /**
     * returns the order item associated with the buyable.
     * ALWAYS returns one, even if there is none in the cart.
     * Does not write to database.
     *
     * @return OrderItem (no kidding)
     **/
    public function OrderItem()
    {
        //work out the filter
        $filterArray = array();
        $extendedFilter = $this->extend('updateItemFilter', $filter);
        if ($extendedFilter !== null && is_array($extendedFilter) && count($extendedFilter)) {
            $filterArray = $extendedFilter;
        }
        //make the item and extend
        $item = ShoppingCart::singleton()->findOrMakeItem($this, $filterArray);
        $this->extend('updateDummyItem', $item);

        return $item;
    }

    /**
     * @var string
     */
    protected $defaultClassNameForOrderItem = 'Product_OrderItem';

    /**
     * you can overwrite this function in your buyable items (such as Product).
     *
     * @return string
     **/
    public function classNameForOrderItem()
    {
        $className = $this->defaultClassNameForOrderItem;
        $updateClassName = $this->extend('updateClassNameForOrderItem', $className);
        if ($updateClassName !== null && is_array($updateClassName) && count($updateClassName)) {
            $className = $updateClassName[0];
        }

        return $className;
    }

    /**
     * You can set an alternative class name for order item using this method.
     *
     * @param string $ClassName
     **/
    public function setAlternativeClassNameForOrderItem($className)
    {
        $this->defaultClassNameForOrderItem = $className;
    }

    /**
     * This is used when you add a product to your cart
     * if you set it to 1 then you can add 0.1 product to cart.
     * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
     *
     * @return int
     **/
    public function QuantityDecimals()
    {
        return 0;
    }

    /**
     * Number of items sold.
     *
     * @return int
     */
    public function HasBeenSold()
    {
        return $this->getHasBeenSold();
    }
    public function getHasBeenSold()
    {
        $dataList = Order::get_datalist_of_orders_with_submit_record($onlySubmittedOrders = true, $includeCancelledOrders = false);
        $dataList = $dataList->innerJoin('OrderAttribute', '"OrderAttribute"."OrderID" = "Order"."ID"');
        $dataList = $dataList->innerJoin('OrderItem', '"OrderAttribute"."ID" = "OrderItem"."ID"');
        $dataList = $dataList->filter(
            array(
                'BuyableID' => $this->ID,
                'buyableClassName' => $this->ClassName
            )
        );

        return $dataList->count();
    }

    //LINKS

    /**
     * Tells us the link to select variations
     * If ajaxified, this controller method (selectvariation)
     * Will return a html snippet for selecting the variation.
     * This is useful in the Product Group where you can both
     * non-variation and variation products to have the same
     * "add to cart" button.  Using this link you can provide a
     * pop-up select system for selecting a variation.
     *
     * @return string
     */
    public function AddVariationsLink()
    {
        return $this->Link('selectvariation');
    }

    /**
     * passing on shopping cart links ...is this necessary?? ...why not just pass the cart?
     *
     * @return string
     */
    public function AddLink()
    {
        return ShoppingCart_Controller::add_item_link($this->ID, $this->ClassName, $this->linkParameters('add'));
    }

    /**
     * link use to add (one) to cart.
     *
     *@return string
     */
    public function IncrementLink()
    {
        //we can do this, because by default add link adds one
        return ShoppingCart_Controller::add_item_link($this->ID, $this->ClassName, $this->linkParameters('increment'));
    }

    /**
     * Link used to remove one from cart
     * we can do this, because by default remove link removes one.
     *
     * @return string
     */
    public function DecrementLink()
    {
        return ShoppingCart_Controller::remove_item_link($this->ID, $this->ClassName, $this->linkParameters('decrement'));
    }

    /**
     * remove one buyable's orderitem from cart.
     *
     * @return string (Link)
     */
    public function RemoveLink()
    {
        return ShoppingCart_Controller::remove_item_link($this->ID, $this->ClassName, $this->linkParameters('remove'));
    }

    /**
     * remove all of this buyable's orderitem from cart.
     *
     * @return string (Link)
     */
    public function RemoveAllLink()
    {
        return ShoppingCart_Controller::remove_all_item_link($this->ID, $this->ClassName, $this->linkParameters('removeall'));
    }

    /**
     * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
     *
     * @return string (Link)
     */
    public function RemoveAllAndEditLink()
    {
        return ShoppingCart_Controller::remove_all_item_and_edit_link($this->ID, $this->ClassName, $this->linkParameters('removeallandedit'));
    }

    /**
     * set new specific new quantity for buyable's orderitem.
     *
     * @param float
     *
     * @return string (Link)
     */
    public function SetSpecificQuantityItemLink($quantity)
    {
        return ShoppingCart_Controller::set_quantity_item_link($this->ID, $this->ClassName, array_merge($this->linkParameters('setspecificquantityitem'), array('quantity' => $quantity)));
    }

    /**
     * @return string
     */
    public function AddToCartAndGoToCheckoutLink()
    {
        $array = $this->linkParameters();
        $array['BackURL'] = urlencode(CheckoutPage::find_link());

        return ShoppingCart_Controller::add_item_link($this->ID, $this->ClassName, $array);
    }

    /**
     *
     *
     * @return string
     */
    public function VersionedLink()
    {
        return Controller::join_links(
             Director::baseURL(),
             EcommerceConfig::get('ShoppingCart_Controller', 'url_segment'),
             'submittedbuyable',
             $this->ClassName,
             $this->ID,
             $this->Version
         );
    }

    public function RemoveFromSaleLink()
    {
        return ShoppingCart_Controller::remove_from_sale_link($this->ID, $this->ClassName);
    }

    /**
     * Here you can add additional information to your product
     * links such as the AddLink and the RemoveLink.
     * One useful parameter you can add is the BackURL link.
     *
     * Usage would be by means of
     * 1. decorating product
     * 2. adding a updateLinkParameters method
     * 3. adding items to the array.
     *
     * You can also extend Product and override this method...
     *
     * @return array
     **/
    protected function linkParameters($type = '')
    {
        $array = array();
        $extendedArray = $this->extend('updateLinkParameters', $array, $type);
        if ($extendedArray !== null && is_array($extendedArray) && count($extendedArray)) {
            foreach ($extendedArray as $extendedArrayUpdate) {
                $array = array_merge($array, $extendedArrayUpdate);
            }
        }

        return $array;
    }

    //TEMPLATE STUFF

    /**
     * @return bool
     */
    public function IsInCart()
    {
        return ($this->OrderItem() && $this->OrderItem()->Quantity > 0) ? true : false;
    }

    /**
     * @return EcomQuantityField
     */
    public function EcomQuantityField()
    {
        return EcomQuantityField::create($this);
    }

    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $EcommerceConfigAjax.TableID.
     *
     * @return EcommerceConfigAjax
     **/
    public function AJAXDefinitions()
    {
        return EcommerceConfigAjax::get_one($this);
    }

    /**
     * @return EcommerceDBConfig
     **/
    public function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }

    /**
     * Is it a variation?
     *
     * @return bool
     */
    public function IsProductVariation()
    {
        return false;
    }

    /**
     * tells us if the current page is part of e-commerce.
     *
     * @return bool
     */
    public function IsEcommercePage()
    {
        return true;
    }

    public function AllowPurchaseNice()
    {
        return $this->obj('AllowPurchase')->Nice();
    }

    /**
     * Products have a standard price, but for specific situations they have a calculated price.
     * The Price can be changed for specific member discounts, etc...
     *
     * @return float
     */
    public function CalculatedPrice()
    {
        return $this->getCalculatedPrice();
    }

    private static $_calculated_price_cache = array();

    /**
     * Products have a standard price, but for specific situations they have a calculated price.
     * The Price can be changed for specific member discounts, etc...
     *
     * We add three "hooks" / "extensions" here... so that you can update prices
     * in a logical order (e.g. firstly change to forex and then apply discount)
     *
     * @return float
     */
    public function getCalculatedPrice($forceRecalculation = false)
    {
        if (! isset(self::$_calculated_price_cache[$this->ID]) || $forceRecalculation) {
            $price = $this->Price;
            $updatedPrice = $this->extend('updateBeforeCalculatedPrice', $price);
            if ($updatedPrice !== null && is_array($updatedPrice) && count($updatedPrice)) {
                $price = $updatedPrice[0];
            }
            $updatedPrice = $this->extend('updateCalculatedPrice', $price);
            if ($updatedPrice !== null && is_array($updatedPrice) && count($updatedPrice)) {
                $price = $updatedPrice[0];
            }
            $updatedPrice = $this->extend('updateAfterCalculatedPrice', $price);
            if ($updatedPrice !== null && is_array($updatedPrice) && count($updatedPrice)) {
                $price = $updatedPrice[0];
            }
            self::$_calculated_price_cache[$this->ID] = $price;
        }
        return self::$_calculated_price_cache[$this->ID];
    }

    /**
     * How do we display the price?
     *
     * @return Money
     */
    public function CalculatedPriceAsMoney()
    {
        return $this->getCalculatedPriceAsMoney();
    }
    public function getCalculatedPriceAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->getCalculatedPrice());
    }

    //CRUD SETTINGS

    /**
     * Is the product for sale?
     *
     * @param Member $member
     * @param bool   $checkPrice
     *
     * @return bool
     */
    public function canPurchase(Member $member = null, $checkPrice = true)
    {
        $config = $this->EcomConfig();
        //shop closed
        if ($config->ShopClosed) {
            return false;
        }
        //not sold at all
        if (! $this->AllowPurchase) {
            return false;
        }
        //check country
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan('canPurchaseByCountry', $member);
        if ($extended !== null) {
            return $extended;
        }
        if (! EcommerceCountry::allow_sales()) {
            return false;
        }

        if ($checkPrice) {
            $price = $this->getCalculatedPrice();
            if ($price == 0 && !$config->AllowFreeProductPurchase) {
                return false;
            }
        }
        // Standard mechanism for accepting permission changes from decorators
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return $this->AllowPurchase;
    }

    public function canCreate($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canCreate($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (is_a(Controller::curr(), Object::getCustomClass('ProductsAndGroupsModelAdmin'))) {
            return false;
        }
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return $this->canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        return $this->canEdit($member);
    }


    public function debug()
    {
        $html = EcommerceTaskDebugCart::debug_object($this);
        $html .= '<ul>';
        $html .= '<li><hr />Links<hr /></li>';
        $html .= '<li><b>Link:</b> <a href="'.$this->Link().'">'.$this->Link().'</a></li>';
        $html .= '<li><b>Ajax Link:</b> <a href="'.$this->AjaxLink().'">'.$this->AjaxLink().'</a></li>';
        $html .= '<li><b>AddVariations Link:</b> <a href="'.$this->AddVariationsLink().'">'.$this->AddVariationsLink().'</a></li>';
        $html .= '<li><b>Add to Cart Link:</b> <a href="'.$this->AddLink().'">'.$this->AddLink().'</a></li>';
        $html .= '<li><b>Increment Link:</b> <a href="'.$this->IncrementLink().'">'.$this->IncrementLink().'</a></li>';
        $html .= '<li><b>Decrement Link:</b> <a href="'.$this->DecrementLink().'">'.$this->DecrementLink().'</a></li>';
        $html .= '<li><b>Remove Link:</b> <a href="'.$this->RemoveAllLink().'">'.$this->RemoveLink().'</a></li>';
        $html .= '<li><b>Remove All Link:</b> <a href="'.$this->RemoveAllLink().'">'.$this->RemoveAllLink().'</a></li>';
        $html .= '<li><b>Remove All and Edit Link:</b> <a href="'.$this->RemoveAllAndEditLink().'">'.$this->RemoveAllAndEditLink().'</a></li>';
        $html .= '<li><b>Set Specific Quantity Item Link (e.g. 77):</b> <a href="'.$this->SetSpecificQuantityItemLink(77).'">'.$this->SetSpecificQuantityItemLink(77).'</a></li>';

        $html .= '<li><hr />Cart<hr /></li>';
        $html .= '<li><b>Allow Purchase (DB Value):</b> '.$this->AllowPurchaseNice().' </li>';
        $html .= '<li><b>Can Purchase (overal calculation):</b> '.($this->canPurchase() ? 'YES' : 'NO').' </li>';
        $html .= '<li><b>Shop Open:</b> '.($this->EcomConfig() ?  ($this->EcomConfig()->ShopClosed ? 'NO' : 'YES') : 'NO CONFIG').' </li>';
        $html .= '<li><b>Extended Country Can Purchase:</b> '.($this->extendedCan('canPurchaseByCountry', null) === null ? 'no applicable' : ($this->extendedCan('canPurchaseByCountry', null) ? 'CAN PURCHASE' : 'CAN NOT PURCHASE')).' </li>';
        $html .= '<li><b>Allow sales to this country ('.EcommerceCountry::get_country().'):</b> '.(EcommerceCountry::allow_sales() ? 'YES' : 'NO').' </li>';
        $html .= '<li><b>Class Name for OrderItem:</b> '.$this->classNameForOrderItem().' </li>';
        $html .= '<li><b>Quantity Decimals:</b> '.$this->QuantityDecimals().' </li>';
        $html .= '<li><b>Is In Cart:</b> '.($this->IsInCart() ? 'YES' : 'NO').' </li>';
        $html .= '<li><b>Has Been Sold:</b> '.($this->HasBeenSold() ?  'YES' : 'NO').' </li>';
        $html .= '<li><b>Calculated Price:</b> '.$this->CalculatedPrice().' </li>';
        $html .= '<li><b>Calculated Price as Money:</b> '.$this->getCalculatedPriceAsMoney()->Nice().' </li>';

        $html .= '<li><hr />Location<hr /></li>';
        $html .= '<li><b>Main Parent Group:</b> '.$this->MainParentGroup()->Title.'</li>';
        $html .= '<li><b>All Others Parent Groups:</b> '.($this->AllParentGroups()->count() ? '<pre>'.print_r($this->AllParentGroups()->map()->toArray(), 1).'</pre>' : 'none').'</li>';

        $html .= '<li><hr />Image<hr /></li>';
        $html .= '<li><b>Image:</b> '.($this->BestAvailableImage() ? '<img src='.$this->BestAvailableImage()->Link().' />' : 'no image').' </li>';
        $productGroup = ProductGroup::get()->byID($this->ParentID);
        if ($productGroup) {
            $html .= '<li><hr />Product Example<hr /></li>';
            $html .= '<li><b>Product Group View:</b> <a href="'.$productGroup->Link().'">'.$productGroup->Title.'</a> </li>';
            $html .= '<li><b>Product Group Debug:</b> <a href="'.$productGroup->Link('debug').'">'.$productGroup->Title.'</a> </li>';
            $html .= '<li><b>Product Group Admin:</b> <a href="'.'/admin/pages/edit/show/'.$productGroup->ID.'">'.$productGroup->Title.' Admin</a> </li>';
            $html .= '<li><b>Edit this Product:</b> <a href="'.'/admin/pages/edit/show/'.$this->ID.'">'.$this->Title.' Admin</a> </li>';
        }
        $html .= '</ul>';

        return $html;
        $html .= '</ul>';

        return $html;
    }

    /**
     *
     * @int
     */
    public function IDForSearchResults()
    {
        return $this->ID;
    }
}

class Product_Controller extends Page_Controller
{
    private static $allowed_actions = array(
        'viewversion',
        'ajaxview',
        'addproductfromform',
        'debug' => 'ADMIN',
    );

    /**
     * is this the current version?
     *
     * @var bool
     */
    protected $isCurrentVersion = true;

    /**
     * Standard SS method.
     */
    public function init()
    {
        parent::init();
        Requirements::themedCSS('Product', 'ecommerce');
        Requirements::javascript('ecommerce/javascript/EcomProducts.js');
    }

    /**
     * view earlier version of a product
     * returns error or changes datarecord to earlier version
     * if the ID does not match the Page then we look for the variation.
     *
     * @param SS_HTTPRequest
     */
    public function viewversion(SS_HTTPRequest $request)
    {
        $version = intval($request->param('ID')) - 0;
        $currentVersion = $this->Version;
        if ($currentVersion != $version) {
            if ($record = $this->getVersionOfBuyable($this->ID, $version)) {
                //we check again, because we may actually get the same version back...
                if ($record->Version != $this->Version) {
                    $this->record = $record;
                    $this->dataRecord->AllowPurchase = false;
                    $this->AllowPurchase = false;
                    $this->isCurrentVersion = false;
                    $this->Title .= _t('Product.OLDERVERSION', ' - Older Version');
                    $this->MetaTitle .= _t('Product.OLDERVERSION', ' - Older Version');
                }
            } else {
                return $this->httpError(404);
            }
        }

        return array();
    }

    /**
     * Standard SS method
     * Returns a snippet when requested by ajax.
     */
    public function ajaxview(SS_HTTPRequest $request)
    {
        Config::nest();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        $html = $this->renderWith('ProductGroupItemMoreDetail');
        Config::unnest();

        return $html;
    }

    /**
     * returns a form for adding products to cart.
     *
     * @return Form
     */
    public function AddProductForm()
    {
        if ($this->canPurchase()) {
            $farray = array();
            $requiredFields = array();
            $fields = new FieldList($farray);
            $fields->push(new NumericField('Quantity', 'Quantity', 1)); //TODO: perhaps use a dropdown instead (elimiates need to use keyboard)
            $actions = new FieldList(
                new FormAction('addproductfromform', _t('Product.ADDLINK', 'Add this item to cart'))
            );
            $requiredfields[] = 'Quantity';
            $validator = new RequiredFields($requiredfields);
            $form = new Form($this, 'AddProductForm', $fields, $actions, $validator);

            return $form;
        } else {
            return _t('Product.PRODUCTNOTFORSALE', 'Product not for sale');
        }
    }

    /**
     * executes the AddProductForm.
     *
     * @param array $data
     * @param Form  $form
     */
    public function addproductfromform(array $data, Form $form)
    {
        if (!$this->IsInCart()) {
            $quantity = round($data['Quantity'], $this->QuantityDecimals());
            if (!$quantity) {
                $quantity = 1;
            }
            $product = Product::get()->byID($this->ID);
            if ($product) {
                ShoppingCart::singleton()->addBuyable($product, $quantity);
            }
            if ($this->IsInCart()) {
                $msg = _t('Order.SUCCESSFULLYADDED', 'Added to cart.');
                $status = 'good';
            } else {
                $msg = _t('Order.NOTADDEDTOCART', 'Not added to cart.');
                $status = 'bad';
            }
            if (Director::is_ajax()) {
                return ShoppingCart::singleton()->setMessageAndReturn($msg, $status);
            } else {
                $form->sessionMessage($msg, $status);
                $this->redirectBack();
            }
        } else {
            return EcomQuantityField::create($this);
        }
    }

    /**
     * Is this an older version?
     *
     * @return bool
     */
    public function IsOlderVersion()
    {
        return $this->isCurrentVersion ? false : true;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return DataList (Products)
     */
    public function SidebarProducts()
    {
        return;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return Product | Null
     */
    public function NextProduct()
    {
        $array = $this->getListOfIDs();
        $next = 0;
        foreach ($array as $key => $id) {
            $id = intval($id);
            if ($id == $this->ID) {
                if (isset($array[$key + 1])) {
                    return Product::get()->byID(intval($array[$key + 1]));
                }
            }
        }
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return Product | Null
     */
    public function PreviousProduct()
    {
        $array = $this->getListOfIDs();
        $previousID = 0;
        foreach ($array as $key => $id) {
            $id = intval($id);
            if ($id == $this->ID) {
                return Product::get()->byID($previousID);
            }
            $previousID = $id;
        }

        return;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return bool
     */
    public function HasPreviousOrNextProduct()
    {
        return $this->PreviousProduct() || $this->NextProduct() ? true : false;
    }

    /**
     * returns an array of product IDs, as saved in the last
     * ProductGroup view (saved using session).
     *
     * @return array
     */
    protected function getListOfIDs()
    {
        $listOfIDs = Session::get(EcommerceConfig::get('ProductGroup', 'session_name_for_product_array'));
        if ($listOfIDs) {
            $arrayOfIDs = explode(',', $listOfIDs);
            if (is_array($arrayOfIDs)) {
                return $arrayOfIDs;
            }
        }

        return array();
    }

    public function debug()
    {
        $member = Member::currentUser();
        if (!$member || !$member->IsShopAdmin()) {
            $messages = array(
                'default' => 'You must login as an admin to access debug functions.',
            );
            Security::permissionFailure($this, $messages);
        }

        return $this->dataRecord->debug();
    }
}
