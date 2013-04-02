<?php
/**
 * describes the buyable classes
 * CONTENT:
 * //GROUPS AND SIBLINGS
 * //IMAGES
 * //VERSIONING
 * //ORDER ITEM
 * //LINKS
 * //TEMPLATE STUFF
 * //CRUD SETTINGS
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: buyables
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

interface BuyableModel {



	//GROUPS AND SIBLINGS

	/**
	 * Returns the direct parent (group) for the product.
	 *
	 * @return Null | DataObject(ProductGroup)
	 **/
	function MainParentGroup();

	/**
	 * Returns Buybales in the same group
	 * @return Null | DataObjectSet
	 **/
	function Siblings();




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
	 * @return Image | Null
	 */
	public function BestAvailableImage();

	/**
	 * Little hack to show thumbnail in summary fields in modeladmin in CMS.
	 * @return String (HTML = formatted image)
	 */
	function CMSThumbnail();

	/**
	 * returns a link to the standard image
	 * @return String
	 */
	public function DefaultImageLink();

	/**
	 * returns a product image for use in templates
	 * e.g. $DummyImage.Width();
	 * @return Product_Image
	 */
	public function DummyImage();




	// VERSIONING

	/**
	 * Action to return specific version of a product.
	 * This can be any product to enable the retrieval of deleted products.
	 * This is really useful for sold products where you want to retrieve the actual version that you sold.
	 * @param Int $id
	 * @param Int $version
	 * @return DataObject | Null
	 */
	function getVersionOfBuyable($id = 0, $version = 0);




	//ORDER ITEM

	/**
	 * returns the order item associated with the buyable.
	 * ALWAYS returns one, even if there is none in the cart.
	 * Does not write to database.
	 * @return OrderItem (no kidding)
	 **/
	public function OrderItem();

	/**
	 *
	 * @var String
	 */
	//protected $defaultClassNameForOrderItem;

	/**
	 * you can overwrite this function in your buyable items (such as Product)
	 * @return String
	 **/
	public function classNameForOrderItem();

	/**
	 * You can set an alternative class name for order item using this method
	 * @param String $ClassName
	 **/
	public function setAlternativeClassNameForOrderItem($className);

	/**
	 * This is used when you add a product to your cart
	 * if you set it to 1 then you can add 0.1 product to cart.
	 * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
	 *
	 * @return Int
	 **/
	function QuantityDecimals();

	/**
	 * Has it been sold?
	 * @return Boolean
	 */
	function HasBeenSold();




	//LINKS

	function Link($action = null);

	/**
	 * passing on shopping cart links ...is this necessary?? ...why not just pass the cart?
	 * @return String
	 */
	function AddLink();

	/**
	 * link use to add (one) to cart
	 *@return String
	 */
	function IncrementLink();

	/**
	 * Link used to remove one from cart
	 * we can do this, because by default remove link removes one
	 * @return String
	 */
	function DecrementLink();

	/**
	 * remove one buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveLink();

	/**
	 * remove all of this buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveAllLink();

	/**
	 * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
	 * @return String (Link)
	 */
	function RemoveAllAndEditLink();

	/**
	 * set new specific new quantity for buyable's orderitem
	 * @param double
	 * @return String (Link)
	 */
	function SetSpecificQuantityItemLink($quantity);





	//TEMPLATE STUFF

	/**
	 *
	 * @return boolean
	 */
	function IsInCart();

	/**
	 *
	 * @return EcomQuantityField
	 */
	function EcomQuantityField();

	/**
	 * returns the instance of EcommerceConfigAjax for use in templates.
	 * In templates, it is used like this:
	 * $EcommerceConfigAjax.TableID
	 *
	 * @return EcommerceConfigAjax
	 **/
	public function AJAXDefinitions();

	/**
	 * returns the instance of EcommerceDBConfig
	 *
	 * @return EcommerceDBConfig
	 **/
	public function EcomConfig();

	/**
	 * Is it a variation?
	 * @return Boolean
	 */
	function IsProductVariation();


	/**
	 * Turn AllowPurchase into Yes or no
	 * @return String
	 */
	function AllowPurchaseNice();

	/**
	 * Products have a standard price, but for specific situations they have a calculated price.
	 * The Price can be changed for specific member discounts, a different currency, etc...
	 * @return Currency (casted variable)
	 */
	function CalculatedPrice();

	/**
	 * Products have a standard price, but for specific situations they have a calculated price.
	 * The Price can be changed for specific member discounts, a different currency, etc...
	 * @return Money
	 */
	function CalculatedPriceAsMoney();



	//CRUD SETTINGS

	/**
	 * Is the product for sale?
	 * @return Boolean
	 */
	function canPurchase($member = null);



}
