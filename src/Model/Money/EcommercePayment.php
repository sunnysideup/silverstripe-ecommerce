<?php

namespace Sunnysideup\Ecommerce\Model\Money;

use CMSEditLinkAPI;












use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Validation\EcommercePaymentFormSetupAndValidation;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;
use Sunnysideup\Ecommerce\Money\EcommercePaymentSupportedMethodsProvider;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;

/**
 * "Abstract" class for a number of different payment
 * types allowing a user to pay for something on a site.
 *
 * This can't be an abstract class because sapphire doesn't
 * support abstract DataObject classes.
 */
class EcommercePayment extends DataObject implements EditableEcommerceObject
{
    /**
     * automatically populated by the dependency manager.
     *
     * @var EcommercePaymentSupportedMethodsProvider
     */
    public $supportedMethodsProvider = null;

    /**
     * standard SS Variable.
     *
     * @var array
     */
    private static $dependencies = [
        'supportedMethodsProvider' => '%$'.EcommercePaymentSupportedMethodsProvider::class,
    ];

    /**
     * Incomplete (default): Payment created but nothing confirmed as successful
     * Success: Payment successful
     * Failure: Payment failed during process
     * Pending: Payment awaiting receipt/bank transfer etc.
     */

    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * OLD: private static $db (case sensitive)
     * NEW:
    private static $db (COMPLEX)
     * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
    private static $table_name = 'EcommercePayment';

    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * WHY: automated upgrade
     * OLD: private static $db = (case sensitive)
     * NEW: private static $db = (COMPLEX)
     * EXP: Make sure to add a private static $table_name!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
    private static $db = [
        'Status' => "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
        'Amount' => 'Money',
        'Message' => 'Text',
        'IP' => 'Varchar(45)', /* for IPv6 you have to make sure you have up to 45 characters */
        'ProxyIP' => 'Varchar(45)',
        'ExceptionError' => 'Text',
        'AlternativeEndPoint' => 'Varchar(255)',
    ];

    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * WHY: automated upgrade
     * OLD: private static $has_one = (case sensitive)
     * NEW: private static $has_one = (COMPLEX)
     * EXP: Make sure to add a private static $table_name!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
    private static $has_one = [
        'PaidBy' => Member::class,
        'Order' => Order::class,
    ];

    private static $summary_fields = [
        'Created' => 'Created',
        'Order.Title' => 'Order',
        'Title' => 'Type',
        'AmountCurrency' => 'Amount',
        'Amount.Nice' => 'Amount',
        'Status' => 'Status',
    ];

    private static $casting = [
        'Title' => 'Varchar',
        'AmountValue' => 'Currency',
        'AmountCurrency' => 'Varchar',
    ];

    private static $searchable_fields = [
        'OrderID' => [

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: NumericField (case sensitive)
             * NEW: NumericField (COMPLEX)
             * EXP: check the number of decimals required and add as ->Step(123)
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            'field' => 'NumericField',
            'title' => 'Order Number',
        ],
        'Created' => [
            'title' => 'Date (e.g. today)',
            'field' => 'TextField',
            'filter' => 'EcommercePaymentFiltersAroundDateFilter',
        ],
        'IP' => [
            'title' => 'IP Address',
            'filter' => 'PartialMatchFilter',
        ],
        'Status',
    ];

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Shop Payment';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Shop Payments';

    private static $indexes = [
        'Status' => true,
        'LastEdited' => true,
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $default_sort = [
        'LastEdited' => 'DESC',
        'ID' => 'DESC',
    ];

    private $ecommercePaymentFormSetupAndValidationObject = null;

    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /**
     * CRUCIAL
     * makes sure all the relevant payment methods are available ...
     *
     * @return $this | EcommercePayment
     */
    public function init()
    {
        self::get_supported_methods($this->Order());

        return $this;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('OrderID', new ReadonlyField('OrderID', 'Order ID'));
        $fields->replaceField('PaidByID', new ReadonlyField('PaidByID', 'Payment made by'));
        $fields->removeByName('AlternativeEndPoint');

        return $fields;
    }

    /**
     * link to edit the record.
     *
     * @param string | Null $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canCreate($member);
    }

    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        $order = $this->Order();
        if ($order && $order->exists()) {
            return $order->canView();
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canCreate($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        if ($this->Status === 'Pending' || $this->Status === 'Incomplete') {
            $extended = $this->extendedCan(__FUNCTION__, $member);
            if ($extended !== null) {
                return $extended;
            }
            if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
                return true;
            }

            return parent::canEdit($member);
        }

        return false;
    }

    /**
     * Standard SS method
     * set to false as a security measure...
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return false;
    }

    /**
     * redirects to this link after order has been placed ...
     * @param  string $link
     */
    public function addAlternativeEndPoint($link, $write = true)
    {
        $this->AlternativeEndPoint = $link;
        if ($write) {
            $this->write();
        }
    }

    /**
     * redirect to order action.
     */
    public function redirectToOrder()
    {
        if ($this->AlternativeEndPoint) {
            return Controller::curr()->redirect(Director::absoluteBaseURL() . $this->AlternativeEndPoint);
        }
        $order = $this->Order();
        if ($order) {
            return Controller::curr()->redirect($order->Link());
        }
        user_error('No order found with this payment: ' . $this->ID, E_USER_NOTICE);
    }

    /**
     * alias
     * @return string
     */
    public function Title()
    {
        return $this->getTitle();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->i18n_singular_name();
    }

    /**
     * alias for getAmountValue
     * @return float
     */
    public function AmountValue()
    {
        return $this->getAmountValue();
    }

    /**
     * @return float
     */
    public function getAmountValue()
    {
        return $this->Amount->getAmount();
    }

    /**
     * alias for getAmountCurrency
     * @return string
     */
    public function AmountCurrency()
    {
        return $this->getAmountCurrency();
    }

    /**
     * @return string
     */
    public function getAmountCurrency()
    {
        return $this->Amount->getCurrency();
    }

    /**
     * standard SS method
     * try to finalise order if payment has been made.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->PaidByID = Member::currentUserID();
    }

    /**
     * standard SS method
     * try to finalise order if payment has been made.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $order = $this->Order();

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD:  Object:: (case sensitive)
         * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
         * EXP: Check if this is the right implementation, this is highly speculative.
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        if ($order && is_a($order, EcommerceConfigClassNames::getName(Order::class)) && $order->IsSubmitted()) {
            $order->tryToFinaliseOrder();
        }
    }

    /**
     *@return string
     **/
    public function Status()
    {
        return _t('Payment.' . strtoupper($this->Status), $this->Status);
    }

    /**
     * Return the site currency in use.
     *
     * @return string
     */
    public static function site_currency()
    {
        $currency = EcommerceConfig::get(EcommerceCurrency::class, 'default_currency');
        if (! $currency) {
            user_error('It is highly recommended that you set a default currency using the config files (EcommerceCurrency.default_currency)', E_USER_NOTICE);
        }

        return $currency;
    }

    /**
     * Set currency to default one.
     * Set IP address.
     */
    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->Amount->Currency = EcommerceConfig::get(EcommerceCurrency::class, 'default_currency');
        $this->setClientIP();
    }

    /**
     * Returns the Payment type currently in use.
     *
     * @return string | null
     */
    public function PaymentMethod()
    {
        $supportedMethods = self::get_supported_methods($this->Order());

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: $this->ClassName (case sensitive)
         * NEW: $this->ClassName (COMPLEX)
         * EXP: Check if the class name can still be used as such
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        if (isset($supportedMethods[$this->ClassName])) {

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: $this->ClassName (case sensitive)
             * NEW: $this->ClassName (COMPLEX)
             * EXP: Check if the class name can still be used as such
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            return $supportedMethods[$this->ClassName];
        }
    }

    /**
     * Static method to quickly update the payment method on runtime
     * associative array that goes like ClassName => Description ...
     *
     * e.g. MyPaymentClass => Best Payment Method Ever     * @param array $array -
     * @param array $array
     */
    public static function set_supported_methods($array)
    {
        Config::modify()->update(EcommercePayment::class, 'supported_methods', null);
        Config::modify()->update(EcommercePayment::class, 'supported_methods', $array);
    }

    /**
     * returns the list of supported methods
     * test methods are included if the site is in DEV mode OR
     * the current user is a ShopAdmin.
     *
     * @return array
     *     [Code] => "Description",
     *     [Code] => "Description",
     *     [Code] => "Description"
     */
    public static function get_supported_methods($order = null): array
    {
        $obj = self::create();

        return $obj->supportedMethodsProvider->SupportedMethods($order);
    }

    /**
     * Return the form requirements for all the payment methods.
     *
     * @param null $order | Array
     *
     * @return An array suitable for passing to CustomRequiredFields
     */
    public static function combined_form_requirements($order = null)
    {
        return;
    }

    /**
     * Return a set of payment fields from all enabled
     * payment methods for this site, given the .
     * is used to define which methods are available.
     *
     * @param string       $amount formatted amount (e.g. 12.30) without the currency
     * @param Order|null $order
     *
     * @return FieldList
     */
    public static function combined_form_fields($amount, $order = null)
    {
        // Create the initial form fields, which defines an OptionsetField
        // allowing the user to choose which payment method to use.
        $supportedMethods = self::get_supported_methods($order);
        $fields = new FieldList(
            $optionsField = new OptionsetField(
                'PaymentMethod',
                '',
                []
            )
        );
        $options = [];
        foreach ($supportedMethods as $methodClass => $methodName) {
            $htmlClassName = self::php_class_to_html_class($methodClass);
            $options[$htmlClassName] = $methodName;
            // Create a new CompositeField with method specific fields,
            // as defined on each payment method class using getPaymentFormFields()
            $methodFields = new CompositeField(
                $methodClass::create()->getPaymentFormFields($amount, $order)
            );
            $methodFields->addExtraClass("methodFields_${htmlClassName}");
            $methodFields->addExtraClass('paymentfields');
            // Add those fields to the initial FieldSet we first created
            $fields->push($methodFields);
        }
        $optionsField->setSource($options);

        // Add the amount and subtotal fields for the payment amount
        $fields->push(new HeaderField('Amount', _t('Payment.AMOUNT_COLON', 'Amount to be charged: ') . '<u class="totalAmountToBeCharged">' . $amount . '</u>', 4));

        return $fields;
    }

    /**
     * Return the payment form fields that should
     * be shown on the checkout order form for the
     * payment type. Example: for {@link DPSPayment},
     * this would be a set of fields to enter your
     * credit card details.
     *
     * @return FieldList
     */
    public function getPaymentFormFields($amount = 0, $order = null)
    {

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: $this->class (case sensitive)
         * NEW: $this->class (COMPLEX)
         * EXP: Check if the class name can still be used as such
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        user_error("Please implement getPaymentFormFields() on {$this->class}", E_USER_ERROR);
    }

    /**
     * Define what fields defined in {@link Order->getPaymentFormFields()}
     * should be required.
     *
     * @see DPSPayment->getPaymentFormRequirements() for an example on how
     * this is implemented.
     *
     * @return array
     */
    public function getPaymentFormRequirements()
    {

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: $this->class (case sensitive)
         * NEW: $this->class (COMPLEX)
         * EXP: Check if the class name can still be used as such
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        user_error("Please implement getPaymentFormRequirements() on {$this->class}", E_USER_ERROR);
    }

    /**
     * Checks if all the data for payment is correct (e.g. credit card)
     * By default it returns true, because lots of payments gatewawys
     * do not have any fields required here.
     *
     * @param array     $data The form request data - see OrderForm
     * @param OrderForm $form The form object submitted on
     */
    public function validatePayment($data, $form)
    {
        return true;
    }

    /**
     * Perform payment processing for the type of
     * payment. For example, if this was a credit card
     * payment type, you would perform the data send
     * off to the payment gateway on this function for
     * your payment subclass.
     *
     * This is used by {@link OrderForm} when it is
     * submitted.
     *
     * @param array     $data The form request data - see OrderForm
     * @param OrderForm $form The form object submitted on
     *
     * @return EcommercePaymentResult
     */
    public function processPayment($data, $form)
    {

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: $this->class (case sensitive)
         * NEW: $this->class (COMPLEX)
         * EXP: Check if the class name can still be used as such
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        user_error("Please implement processPayment() on {$this->class}", E_USER_ERROR);
    }

    public function PaidObject()
    {
        return $this->Order();
    }

    /**
     * Debug helper method.
     * Access through : /shoppingcart/debug/.
     */
    public function debug()
    {
        return EcommerceTaskDebugCart::debug_object($this);
    }

    /**
     * @return EcommercePaymentFormSetupAndValidation
     */
    public static function ecommerce_payment_form_setup_and_validation_object()
    {
        return Injector::inst()->create(EcommercePaymentFormSetupAndValidation::class);
    }

    public static function php_class_to_html_class(string $phpClass): string
    {
        return str_replace('\\', '-', $phpClass);
    }

    public static function html_class_to_php_class(string $htmlClass): string
    {
        return str_replace('-', '\\', $htmlClass);
    }

    /**
     * Set the IP address of the user to this payment record.
     * This isn't perfect - IP addresses can be hidden fairly easily.
     */
    protected function setClientIP()
    {
        $proxy = null;
        $ip = null;
        if (Controller::has_curr()) {
            $ip = Controller::curr()->getRequest()->getIP();
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //swapsies
            $proxy = $ip;
        }

        // Only set the IP and ProxyIP if none currently set
        if (! $this->IP) {
            $this->IP = $ip;
        }
        if (! $this->ProxyIP) {
            $this->ProxyIP = $proxy;
        }
    }

    protected function handleError($e)
    {
        $this->ExceptionError = $e->getMessage();
        $this->write();
    }

    /**
     * @return EcommercePaymentFormSetupAndValidation
     */
    protected function ecommercePaymentFormSetupAndValidationObject()
    {
        if (! $this->ecommercePaymentFormSetupAndValidationObject) {
            $this->ecommercePaymentFormSetupAndValidationObject = Injector::inst()->create(EcommercePaymentFormSetupAndValidation::class);
        }

        return $this->ecommercePaymentFormSetupAndValidationObject;
    }
}
