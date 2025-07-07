<?php

namespace Sunnysideup\Ecommerce\Pages;

use Page;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Traits\OrderCached;

/**
 * Class \Sunnysideup\Ecommerce\Pages\CartPage
 *
 * @property string $ContinueShoppingLabel
 * @property string $ProceedToCheckoutLabel
 * @property string $ShowAccountLabel
 * @property string $CurrentOrderLinkLabel
 * @property string $LoginToOrderLinkLabel
 * @property string $SaveOrderLinkLabel
 * @property string $LoadOrderLinkLabel
 * @property string $DeleteOrderLinkLabel
 * @property string $NoItemsInOrderMessage
 * @property string $NonExistingOrderMessage
 */
class CartPage extends Page
{
    use OrderCached;

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $icon = 'sunnysideup/ecommerce: client/images/icons/CartPage-file.gif';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $table_name = 'CartPage';

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
        'ContinueShoppingLabel' => 'Continue shopping',
        'ProceedToCheckoutLabel' => 'Proceed to checkout',
        'ShowAccountLabel' => 'View account details',
        'CurrentOrderLinkLabel' => 'View current order',
        'LoginToOrderLinkLabel' => 'You must log in to view this order',
        'SaveOrderLinkLabel' => 'Save current order',
        'DeleteOrderLinkLabel' => 'Delete this order',
        'LoadOrderLinkLabel' => 'Finalise this order',
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

    /**
     * Standard SS function, we only allow for one CartPage page to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return CartPage::get()->Filter(['ClassName' => CartPage::class])->exists() ? false : $this->canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
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
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return $this->canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        return $this->canEdit($member);
    }

    /**
     * @return \SilverStripe\Forms\FieldList
     */
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
                        new TextField('ShowAccountLabel', _t('CartPage.SHOWACCOUNTLABEL', "Label on the link 'view account details' - e.g. click here to view your account details")),
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
     *
     * @param string $action [optional]
     *
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
     * @param int|string $orderID - not used in CartPage
     *
     * @return string (URLSegment)
     */
    public static function new_order_link($orderID)
    {
        return Controller::join_links(self::find_link(), 'startneworder', $orderID);
    }

    /**
     * Returns the "copy order" link.
     *
     * @param int|string $orderID - not used in CartPage
     *
     * @return string (URLSegment)
     */
    public static function copy_order_link($orderID)
    {
        return Controller::join_links(self::find_link(), 'copyorder', $orderID);
    }

    /**
     * Return a link to view the order on this page.
     *
     * @param int|string $orderID ID of the order
     *
     * @return int|string (URLSegment)
     */
    public static function get_order_link($orderID)
    {
        return Controller::join_links(self::find_link(), 'showorder', $orderID);
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

    public function SendLoginLinkLink()
    {
        return $this->Link('sendloginlink');
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
     * @return string (HTML Snippet)
     */
    public function EcommerceMenuTitle()
    {
        $count = 0;
        $order = ShoppingCart::current_order();
        if ($order) {
            $count = $order->TotalItems();
            $oldSSViewer = Config::inst()->get(SSViewer::class, 'source_file_comments');
            Config::modify()->set(SSViewer::class, 'source_file_comments', false);
            $this->customise(['Count' => $count, 'OriginalMenuTitle' => $this->MenuTitle]);
            $s = $this->renderWith('Sunnysideup\Ecommerce\Includes\AjaxNumItemsInCart');
            Config::modify()->set(SSViewer::class, 'source_file_comments', $oldSSViewer);

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

    // For use in templates

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
