<?php

namespace Sunnysideup\Ecommerce\Interfaces;

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;

/**
 * describes the buyable classes
 * CONTENT:
 * //GROUPS AND SIBLINGS
 * //IMAGES
 * //VERSIONING
 * //ORDER ITEM
 * //LINKS
 * //TEMPLATE STUFF
 * //CRUD SETTINGS.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: buyables
 */
interface BuyableModel
{
    //GROUPS AND SIBLINGS

    /**
     * Returns the direct parent (group) for the product.
     */
    public function MainParentGroup();

    /**
     * Returns Buybales in the same group.
     *
     * @return \SilverStripe\ORM\DataList (Products)
     */
    public function Siblings();

    //IMAGES

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
     * @return null|\SilverStripe\Assets\Image
     */
    public function BestAvailableImage();

    /**
     * Little hack to show thumbnail in summary fields in modeladmin in CMS.
     *
     * @return string (HTML = formatted image)
     */
    public function CMSThumbnail();

    /**
     * returns a link to the standard image.
     *
     * @return string
     */
    public function DefaultImageLink();

    // VERSIONING

    /**
     * Action to return specific version of a product.
     * This can be any product to enable the retrieval of deleted products.
     * This is really useful for sold products where you want to retrieve the actual version that you sold.
     *
     * @param int $id
     * @param int $version
     *
     * @return null|\SilverStripe\ORM\DataObject
     */
    public function getVersionOfBuyable($id = 0, $version = 0);

    //ORDER ITEM

    /**
     * returns the order item associated with the buyable.
     * ALWAYS returns one, even if there is none in the cart.
     * Does not write to database.
     *
     * @return \Sunnysideup\Ecommerce\Model\OrderItem (no kidding)
     */
    public function OrderItem();

    //protected $defaultClassNameForOrderItem;

    /**
     * you can overwrite this function in your buyable items (such as Product).
     *
     * @return string
     */
    public function classNameForOrderItem();

    /**
     * You can set an alternative class name for order item using this method.
     *
     * @param string $className
     */
    public function setAlternativeClassNameForOrderItem($className): static;

    /**
     * This is used when you add a product to your cart
     * if you set it to 1 then you can add 0.1 product to cart.
     * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
     *
     * @return int
     */
    public function QuantityDecimals();

    /**
     * Has it been sold?
     */
    public function HasBeenSold(): bool;

    //LINKS

    /**
     * passing on shopping cart links ...is this necessary?? ...why not just pass the cart?
     *
     * @return string
     */
    public function AddLink();

    /**
     * link use to add (one) to cart.
     *
     * @return string
     */
    public function IncrementLink();

    /**
     * Link used to remove one from cart
     * we can do this, because by default remove link removes one.
     *
     * @return string
     */
    public function DecrementLink();

    /**
     * remove one buyable's orderitem from cart.
     *
     * @return string (Link)
     */
    public function RemoveLink();

    /**
     * remove all of this buyable's orderitem from cart.
     *
     * @return string (Link)
     */
    public function RemoveAllLink();

    /**
     * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
     *
     * @return string (Link)
     */
    public function RemoveAllAndEditLink();

    /**
     * set new specific new quantity for buyable's orderitem.
     *
     * @param float $quantity
     *
     * @return string (Link)
     */
    public function SetSpecificQuantityItemLink($quantity): string;

    /**
     * @return string
     */
    public function AddToCartAndGoToCheckoutLink();

    //TEMPLATE STUFF

    /**
     * @return bool
     */
    public function IsInCart();

    /**
     * @return \Sunnysideup\Ecommerce\Forms\Fields\EcomQuantityField
     */
    public function EcomQuantityField();

    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $EcommerceConfigAjax.TableID.
     *
     * @return \Sunnysideup\Ecommerce\Config\EcommerceConfigAjax
     */
    public function AJAXDefinitions();

    /**
     * Is it a variation?
     *
     * @return bool
     */
    public function IsProductVariation();

    /**
     * Turn AllowPurchase into Yes or no.
     *
     * @return string
     */
    public function AllowPurchaseNice();

    /**
     * Products have a standard price, but for specific situations they have a calculated price.
     * The Price can be changed for specific member discounts, a different currency, etc...
     *
     * @return float (casted variable)
     */
    public function CalculatedPrice();

    public function getCalculatedPrice(?bool $recalculate = false);

    /**
     * How do we display the price?
     *
     * @return \SilverStripe\ORM\FieldType\DBMoney
     */
    public function CalculatedPriceAsMoney(?bool $recalculate = false);

    public function getCalculatedPriceAsMoney(?bool $recalculate = false);

    //CRUD SETTINGS

    /**
     * Is the product for sale?
     *
     * @param mixed $checkPrice
     *
     * @return bool
     */
    public function canPurchase(Member $member = null, $checkPrice = true);


    public function getMinValueInOrder();

    public function getMaxValueInOrder();

    public function SalesOrderItems(): DataList;
}
