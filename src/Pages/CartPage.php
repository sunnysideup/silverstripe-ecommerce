<?php

namespace Sunnysideup\Ecommerce\Pages;

use Page;









use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * @description: This is a page that shows the cart content,
 * without "leading to" checking out. That is, there is no "next step" functionality
 * or a way to submit the order.
 * NOTE: both the Account and the Checkout Page extend from this class as they
 * share some functionality.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages

 **/
class CartPage extends Page
{
    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $icon = 'ecommerce/images/icons/CartPage';

    /**
     * Standard SS variable.
     *
     * @var array
     */

    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * OLD: private static $db (case sensitive)
     * NEW:
    private static $db (COMPLEX)
     * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
    private static $table_name = 'CartPage';

    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * WHY: automated upgrade
     * OLD: private static $db = (case sensitive)
     * NEW: private static $db = (COMPLEX)
     * EXP: Make sure to add a private static $table_name!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
    private static $db = [
        'ContinueShoppingLabel' => 'Varchar(100)',
        'ProceedToCheckoutLabel' => 'Varchar(100)',
        'ShowAccountLabel' => 'Varchar(100)',
        'CurrentOrderLinkLabel' => 'Varchar(100)',
        'LoginToOrderLinkLabel' => 'Varchar(100)',
        'SaveOrderLinkLabel' => 'Varchar(100)',
        'LoadOrderLinkLabel' => 'Varchar(100)',
        'DeleteOrderLinkLabel' => 'Varchar(100)',
        'NoItemsInOrderMessage' => 'HTMLText',
        'NonExistingOrderMessage' => 'HTMLText',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $defaults = [
        'ContinueShoppingLabel' => 'continue shopping',
        'ProceedToCheckoutLabel' => 'proceed to checkout',
        'ShowAccountLabel' => 'view account details',
        'CurrentOrderLinkLabel' => 'view current order',
        'LoginToOrderLinkLabel' => 'you must log in to view this order',
        'SaveOrderLinkLabel' => 'save current order',
        'DeleteOrderLinkLabel' => 'delete this order',
        'LoadOrderLinkLabel' => 'finalise this order',
        'NoItemsInOrderMessage' => '<p>You do not have any items in your current order.</p>',
        'NonExistingOrderMessage' => '<p>Sorry, the order you are trying to open does not exist.</p>',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'MenuTitle' => 'HTMLVarchar',
    ];

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Cart Page';

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Cart Pages';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page where the customer can view the current order (cart) without finalising the order.';

    public function i18n_singular_name()
    {
        return _t('CartPage.SINGULARNAME', 'Cart Page');
    }

    public function i18n_plural_name()
    {
        return _t('CartPage.PLURALNAME', 'Cart Pages');
    }

    /***
     * override core function to turn "checkout" into "Checkout (1)"
     * @return DBField
     */
    public function obj($fieldName, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null)
    {
        if ($fieldName === 'MenuTitle' && ! ($this instanceof OrderConfirmationPage)) {
            return DBField::create_field('HTMLVarchar', strip_tags($this->EcommerceMenuTitle()), 'MenuTitle', $this);
        }
        return parent::obj($fieldName);
    }

    /**
     * Standard SS function, we only allow for one CartPage page to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return CartPage::get()->Filter(['ClassName' => CartPage::class])->Count() ? false : $this->canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
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
    public function canDelete($member = null, $context = [])
    {
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

    /**
     *@return FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Messages',
            [
                new TabSet(
                    'Messages',
                    Tab::create(
                        'Actions',
                        _t('CartPage.ACTIONS', 'Actions'),
                        new TextField('ContinueShoppingLabel', _t('CartPage.CONTINUESHOPPINGLABEL', 'Label on link to continue shopping - e.g. click here to continue shopping')),
                        new TextField('ProceedToCheckoutLabel', _t('CartPage.PROCEEDTOCHECKOUTLABEL', 'Label on link to proceed to checkout - e.g. click here to finalise your order')),
                        new TextField('ShowAccountLabel', _t('CartPage.SHOWACCOUNTLABEL', 'Label on the link \'view account details\' - e.g. click here to view your account details')),
                        new TextField('CurrentOrderLinkLabel', _t('CartPage.CURRENTORDERLINKLABEL', 'Label for the link pointing to the current order - e.g. click here to view current order')),
                        new TextField('LoginToOrderLinkLabel', _t('CartPage.LOGINTOORDERLINKLABEL', 'Label for the link pointing to the order which requires a log in - e.g. you must login to view this order')),
                        new TextField('SaveOrderLinkLabel', _t('CartPage.SAVEORDERLINKLABEL', 'Label for the saving an order - e.g. click here to save current order')),
                        new TextField('LoadOrderLinkLabel', _t('CartPage.LOADORDERLINKLABEL', 'Label for the loading an order into the cart - e.g. click here to finalise this order')),
                        new TextField('DeleteOrderLinkLabel', _t('CartPage.DELETEORDERLINKLABEL', 'Label for the deleting an order - e.g. click here to delete this order'))
                    ),
                    Tab::create(
                        'Errors',
                        _t('CartPage.ERRORS', 'Errors'),
                        $htmlEditorField1 = new HTMLEditorField('NoItemsInOrderMessage', _t('CartPage.NOITEMSINORDERMESSAGE', 'No items in order - shown when the customer tries to view an order without items.')),
                        $htmlEditorField2 = new HTMLEditorField('NonExistingOrderMessage', _t('CartPage.NONEXISTINGORDERMESSAGE', 'Non-existing Order - shown when the customer tries to load a non-existing order.'))
                    )
                ),
            ]
        );
        $htmlEditorField1->setRows(3);
        $htmlEditorField2->setRows(3);

        return $fields;
    }

    /**
     * Returns the Link to the CartPage on this site.
     * @param string $action [optional]
     * @return string (URLSegment)
     */
    public static function find_link($action = null)
    {
        $page = DataObject::get_one(CartPage::class, ['ClassName' => CartPage::class]);
        if ($page) {
            return $page->Link($action);
        }
        return CheckoutPage::find_link($action);
    }

    /**
     * Returns the "new order" link.
     *
     * @param int | String $orderID - not used in CartPage
     *
     * @return string (URLSegment)
     */
    public static function new_order_link($orderID)
    {
        return self::find_link() . 'startneworder/';
    }

    /**
     * Returns the "copy order" link.
     *
     * @param int | String $orderID - not used in CartPage
     *
     * @return string (URLSegment)
     */
    public static function copy_order_link($orderID)
    {
        return OrderConfirmationPage::find_link() . 'copyorder/' . $orderID . '/';
    }

    /**
     * Return a link to view the order on this page.
     *
     * @param int|string $orderID ID of the order
     *
     * @return int | String (URLSegment)
     */
    public static function get_order_link($orderID)
    {
        return self::find_link() . 'showorder/' . $orderID . '/';
    }

    /**
     * Return a link to view the order on this page.
     *
     * @param int|string $orderID ID of the order
     *
     * @return string (URLSegment)
     */
    public function getOrderLink($orderID)
    {
        return self::get_order_link($orderID);
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

    /**
     *@return string (HTML Snippet)
     **/
    public function EcommerceMenuTitle()
    {
        $count = 0;
        $order = ShoppingCart::current_order();
        if ($order) {
            $count = $order->TotalItems();
            $oldSSViewer = Config::inst()->get(SSViewer::class, 'source_file_comments');
            Config::inst()->update(SSViewer::class, 'source_file_comments', false);
            $this->customise(['Count' => $count, 'OriginalMenuTitle' => $this->MenuTitle]);
            $s = $this->renderWith('AjaxNumItemsInCart');
            Config::inst()->update(SSViewer::class, 'source_file_comments', $oldSSViewer);

            return $s;
        }

        return $this->OriginalMenuTitle();
    }

    /**
     * The original menu title of the page.
     *
     * @return string
     */
    public function OriginalMenuTitle()
    {
        return $this->MenuTite;
    }

    /***********************
     * For use in templates
     ***********************/

    /**
     * standard SS method for use in templates.
     *
     * @return string
     */
    public function LinkingMode()
    {
        return parent::LinkingMode() . ' cartlink cartlinkID_' . $this->ID;
    }

    /**
     * standard SS method for use in templates.
     *
     * @return string
     */
    public function LinkOrSection()
    {
        return parent::LinkOrSection() . ' cartlink';
    }

    /**
     * standard SS method for use in templates.
     *
     * @return string
     */
    public function LinkOrCurrent()
    {
        return parent::LinkOrCurrent() . ' cartlink';
    }
}
