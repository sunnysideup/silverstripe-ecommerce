<?php

namespace Sunnysideup\Ecommerce\Config;

use Page;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\SiteConfig\SiteConfig;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Cms\ProductConfigModelAdmin;
use Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin;
use Sunnysideup\Ecommerce\Cms\SalesAdmin;
use Sunnysideup\Ecommerce\Cms\StoreAdmin;
use Sunnysideup\Ecommerce\Control\CartResponse;
use Sunnysideup\Ecommerce\Control\EcommercePaymentController;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Email\OrderEmail;
use Sunnysideup\Ecommerce\Forms\Fields\ExpiryDateField;
use Sunnysideup\Ecommerce\Forms\OrderFormAddress;
use Sunnysideup\Ecommerce\Forms\OrderModifierForm;
use Sunnysideup\Ecommerce\Forms\OrderStatusLogForm;
use Sunnysideup\Ecommerce\Forms\ProductSearchForm;
use Sunnysideup\Ecommerce\Forms\Validation\ShopAccountFormValidator;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Sunnysideup\Ecommerce\Model\Address\EcommerceRegion;
use Sunnysideup\Ecommerce\Model\Address\OrderAddress;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\OrderModifier;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepConfirmed;
use Sunnysideup\Ecommerce\Money\EcommerceMoney;
use Sunnysideup\Ecommerce\Pages\CartPageController;
use Sunnysideup\Ecommerce\Pages\CheckoutPageController;
use Sunnysideup\Ecommerce\Pages\OrderConfirmationPageController;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * This class sets out the static config variables for e-commerce.
 * It also adds the definitions of any classes that extend EcommerceConfigDefitions.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration

 **/
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskCartCleanup;

/**
 * This class sets out the static config variables for e-commerce.
 * It also adds the definitions of any classes that extend EcommerceConfigDefitions.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration
 **/
class EcommerceConfigDefinitions
{
    use Extensible;
    use Injectable;
    use Configurable;

    /**
     * LIST of ajax methods.
     */
    protected $ajaxMethods = [
        'SideBarCartID' =>
            'The sidebar cart. See CartResponse.cart_responses_required to see if it is being used and what template is being used.',

        'SmallCartID' =>
            'The small cart. See CartResponse.cart_responses_required to see if it is being used and what template is being used.',

        'TinyCartClassName' =>
            'The tiny cart. See CartResponse.cart_responses_required to see if it is being used and what template is being used. ',

        'TotalItemsClassName' =>
            'The total number of items in the order. Use in the following context: AjaxDefinitions.TotalItemsClassName',

        'TotalItemsTimesQuantityClassName' =>
            'The total number of items times their quantity in the order.
            Use in the following context: AjaxDefinitions.TotalItemsClassNameTimesQuantity',

        'TableID' =>
            'The main definition on which a lot of others are based.
            Use in the following context: Order.AjaxDefinitions.TableID OR OrderModifier.AjaxDefinitions.TableID OR OrderItem.AjaxDefinitions.TableID',

        'TableTotalID' =>
            'The total cost.
            Use in the following context: Order.AjaxDefinitions.TableTotalID OR OrderModifier.AjaxDefinitions.TableTotalID OR OrderItem.AjaxDefinitions.TableTotalID',

        'HiddenPageTitleID' =>
            'The ID used to identify a (hidden) element that contains the title of the page.
            This can be used for ajax updates of the product list.
            It will be used to update the title of the page. For example, we can change the PageTitle to MyPage - sorted by Price.',

        'ProductListHolderID' =>
            'The ID used to identify the product group list holder.
            This list can be replaced using ajax. ',

        'ProductListAjaxifiedLinkClassName' =>
            'The class name use for sections that contain links for showing alternative views of the product group list. ',

        'ProductListItemClassName' =>
            'The class used to identify each LI item in the list of product items on the product group page (or elsewhere).',

        'ProductListItemInCartClassName' =>
            'The class used to identify the product actions of each LI list item of the list of products that is in the cart right now.',

        'ProductListItemNotInCartClassName' =>
            'The class used to identify each LI item of the list of products that is NOT in the cart right now.',

        'TableMessageID' =>
            'The cart message (e.g. product added). Use in the following context: Order.AjaxDefinitions.TableMessageID',

        'TableSubTotalID' =>
            'The sub-total for the order. Use in the following context: Order.AjaxDefinitions.TableMessageID',

        'ExpectedCountryClassName' =>
            'The holder of the expected country name. Use in the following context: AjaxDefinitions.ExpectedCountryClassName',

        'CountryFieldID' =>
            'The field used for selecting the country. Use in the following context: AjaxDefinitions.CountryFieldID',

        'RegionFieldID' =>
            'The field used for selecting the region. Use in the following context: AjaxDefinitions.RegionFieldID',

        'TableTitleID' =>
            'The title for the item in the checkout page.
            Use in the following context: OrderItem.AjaxDefinitions.TableTitleID OR OrderModifier.AjaxDefinitions.TableTitleID',

        'CartTitleID' =>
            'The title for the item in the cart (not on the checkout page).
            Use in the following context: OrderItem.AjaxDefinitions.CartTitleID OR OrderModifier.AjaxDefinitions.CartTitleID',

        'TableSubTitleID' =>
            'The sub-title for the item in the checkout page.
            Use in the following context: OrderItem.AjaxDefinitions.TableSubTitleID OR OrderModifier.AjaxDefinitions.TableSubTitleID ',

        'CartSubTitleID' =>
            'The sub-title for the item in the cart (not on the checkout page).
                Use in the following context: OrderItem.AjaxDefinitions.CartSubTitleID OR OrderModifier.AjaxDefinitions.CartSubTitleID ',
        'QuantityFieldName' =>
            'The quantity field for the order item.
            Use in the following context: OrderItem.AjaxDefinitions.QuantityFieldName',

        'UniqueIdentifier' =>
            'Unique identifier for the buyable (product).
            Use in the following context: Buyable.AjaxDefinitions.UniqueIdentifier',
    ];

    /**
     * Tells us what version of e-commerce we are using.
     *
     * @var float
     */
    private $version = 1;

    private $definitionGrouping = [
        'GENERAL AND CMS CONFIG' => [
            EcommerceDBConfig::class,
            SiteConfig::class,
            StoreAdmin::class,
            ProductsAndGroupsModelAdmin::class,
            ProductConfigModelAdmin::class,
            SalesAdmin::class,
        ],
        'TEMPLATES' => [
            'Templates',
            EcommerceConfigAjax::class,
        ],
        'PRODUCTS' => [
            ProductGroup::class,
            ProductSearchForm::class,
        ],
        'ORDER OBJECTS' => [
            Order::class,
            OrderItem::class,
            OrderModifier::class,
        ],
        'CART' => [
            ShoppingCart::class,
            ShoppingCartController::class,
            CartResponse::class,
        ],
        'CHECKOUT' => [
            Page::class,
            CartPageController::class,
            CheckoutPageController::class,
            ShopAccountFormValidator::class,
            OrderModifierForm::class,
            EcommercePaymentController::class,
            OrderFormAddress::class,
        ],
        'POST SALE PROCESSING' => [
            OrderConfirmationPageController::class,
            OrderStep::class,
            OrderStepConfirmed::class,
            OrderStatusLog::class,
            OrderStatusLogForm::class,
            Email::class,
            OrderEmail::class,
        ],
        'CUSTOMERS' => [
            OrderAddress::class,
            EcommerceRole::class,
            BillingAddress::class,
            ShippingAddress::class,
            EcommerceCountry::class,
            EcommerceRegion::class,
        ],
        'PAYMENT AND MONEY' => [
            EcommerceCurrency::class,
            EcommerceMoney::class,
            EcommercePayment::class,
            ExpiryDateField::class,
        ],
        'CLEANUP AND OTHER TASKS' => [
            EcommerceTaskCartCleanup::class,
        ],
    ];

    /**
     * Tells us the version of e-commerce in use.
     *
     * @return float
     */
    public function Version()
    {
        return $this->version;
    }

    /**
     * returns defition of Ajax Method.
     *
     * @param string $name
     *
     * @return string
     */
    public function getAjaxMethod($name)
    {
        return $this->ajaxMethods[$name];
    }

    /**
     * returns the definition of an ajax definition.
     *
     * @return array
     */
    public function getAjaxMethods($name = '')
    {
        return $this->ajaxMethods;
    }

    /**
     * @return array
     */
    public function GroupDefinitions()
    {
        return $this->definitionGrouping + ['OTHER' => []];
    }

    /**
     * Get a list of all definitions required for e-commerce.
     * We have this here so that we can check that all static variables have been defined.
     * We can also use this list for clean formatting.
     *
     * This list is for developers only
     *
     * @param string $className - only return for this class name
     * @param string $variable #variable - only return this variable (must define class name as well)
     *
     * @return array | String
     */
    public function Definitions($className = '', $variable = '')
    {
        $array = [
            ################### GENERAL AND CMS CONFIG #####################
            EcommerceDBConfig::class => [

                'ecommerce_db_config_class_name' =>
                    'Class Name for the DataObject that contains the settings for the e-commerce application',

                'array_of_buyables' => '
                    Array of classes (e.g. Product,ProductVariation, ...) that are buyable.
                    You do not need to include a class that extends a buyable.
                    For example, if you create a class called \'MyProduct\' extending Product then you do not need to list it here.',
            ],

            EcommerceConfigAjax::class => [

                'definitions_class_name' => 'Class Name (string) for the class used to define and name all the ajax IDs and Classes.',

                'cart_js_file_location' =>
                    'The location for the EcomCart.js (javascipt that runs the cart) file.
                    The default one is ecommerce/javascript/EcomCart.js',

                'dialogue_js_file_location' => '
                    The location for the dialogue (pop-up) javascript.
                    E-commerce comes with it a default <i>Simple Dialogue</i> pop-up dialogue,
                    but you can also use your own (e.g. prettyPhoto or Greybox).',
                'quantity_field_js_location' => '
                    Ecom Quantity Field javascript location.',
            ],

            StoreAdmin::class => [

                'managed_models' =>
                    'An array of data object classes that are managed as \'Store\' configuration items.
                    This configuration is used a lot to add extra menu items. ',
            ],

            ProductsAndGroupsModelAdmin::class => [

                'managed_models' =>
                    'An array of data object classes that are managed as Products and Product Groups ',
            ],

            ProductConfigModelAdmin::class => [

                'managed_models' =>
                    'An array of data object classes that are managed as \'Product Config\' configuration items.
                    These are all items that relate to Products and Product Groups that are not in the main group.
                    This includes any searches carried out in the Product Group. ', ],

            SalesAdmin::class => [
                'managed_models' =>
                    'An array of data object classes that are managed as \'Store\' configuration items.
                    This configuration is used a lot to add extra menu items. ', ],

            ################### PRODUCT DISPLAY #####################
            ProductGroup::class => [

                'base_buyable_class' =>
                    'The base class for the products being retrieved.
                    Usually this is Product, but it can also be MyProduct or MyProductAsDataObject or anything else that implements the Buyable Interface.',

                'maximum_number_of_products_to_list' =>
                    'The maximum number of products to be shown in a list.
                    For performance reasons, we suggest you limit this to 1000 for big sites and as low as 200 for small servers.',

                'sort_options' =>
                    'Associative sort options array with sub-keys of Title and SQL, e.g. \'default\' = array(\'Title\' => \'default\', \'SQL\' => \'Title DESC\')',

                'filter_options' =>
                    'Associative filters options array with sub-keys of Title and SQL, e.g. \'default\' = array(\'Title\' => \'Featured\', \'SQL\' => \'Featured = 1\')',

                'display_styles' =>
                    'Associative display styles array with its key as template name, e.g. \'MyTemplateName\' => \'Full Details\'',

                'session_name_for_product_array' =>
                    'This is the name for variable stored in session.
                    The variable stores a list of IDs for products being shown on the product group.
                    We store this so that we can go previous and next for each product. ',

            ],

            ProductGroupSearchPage::class => [

                'best_match_key' =>
                    'Sort key used to sort by relevance.',

                'sort_options' =>
                    'Additional associative sort options array with sub-keys of Title and SQL,
                    e.g. \'default\' = array(\'Title\' => \'default\', \'SQL\' => \'Title DESC\') for searching.
                    Include on with the best_match_key.',

                'maximum_number_of_products_to_list_for_search' =>
                    'Maximum number of products returned in search.',
            ],

            Product::class => [

                'folder_name_for_images' =>
                    'Default folder for uploading product images.',
            ],

            ProductSearchForm::class => [

                'include_price_filters' =>
                    'For the product search form,
                    make sure that there are no filter fields for minimum and maximum price',

                'product_session_variable' =>
                    'Name of a session variable used to tell the website what products - based on a search - are to be shown',

                'product_group_session_variable' =>
                    'Name of the session variable used to tell the website what products GROUPS - based on a search - are to be shown',
            ],

            ################### CART AND CHECKOUT PROCESS #####################
            ShoppingCart::class => [

                'session_code' =>
                    'The code use for the session variable that stores the Order ID.',

                'cleanup_every_time' =>
                    'Are carts are cleaned up all the time
                    (if this is set to FALSE then we recommend you setup a cron job to clean old carts)
                    - this is recommended on large sites where any run-time activity will slow the site down
                    (it is more efficient to clear 1000 carts once an hour than to clear 1 cart ever second))?',

                'default_param_filters' =>
                    'Advanced filtering in the shopping cart.  Not currently being used. ',

                'response_class' =>
                    'Class used for ajax responses.',
            ],

            ShoppingCartController::class => [

                'url_segment' =>
                    'URL Segment used for the shopping cart.',
            ],

            CartResponse::class => [

                'cart_responses_required' =>
                    'An array of the cart responses required for AJAX.
                    This array also identifies the unique IDs used in the html that will be updated by the ajax response.',
            ],

            CartPageController::class => [

                'session_code' =>
                    'Code name for session variable used in Cart Page.
                    This session variable is used to retain a message.',
            ],

            CheckoutPageController::class => [

                'checkout_steps' =>
                    'The Checkout Steps.  This can be defined as you like,
                    but the last step should always be: orderconfirmationandpayment.',

                'ajaxify_steps' =>
                    'Array of Javascript files that are required to ajaxify the steps in the checkout.
                    Defaults to none, but there is a sample JS file available: ecommerce/javascript/EcomCheckoutPage.js.',
            ],

            ShopAccountFormValidator::class => [

                'minimum_password_length' =>
                    'The minimum length of the password for an account.',
            ],

            OrderModifierForm::class => [

                'controller_class' =>
                    'The controller class is used for Order Modifier Forms.',

                'validator_class' => 'The validator class is used for Order Modifier Forms.',
            ],

            EcommercePaymentController::class => [

                'url_segment' => 'URL Segment used for the payment process.',
            ],

            OrderFormAddress::class => [

                'shipping_address_first' =>
                    'Show the shipping address before the billing address.
                    This is a better option if it is likely that you have a billing address that is not the same as the shipping address.',
            ],

            ################### POST SALE PROCESSING #####################
            OrderConfirmationPageController::class => [

                'include_as_checkout_step' =>
                    'Include the order confirmation as one of the checkout steps, visually, in the list of steps shown.',
            ],
            OrderStep::class => [

                'order_steps_to_include' =>
                    'Another very important definition.
                    These are the steps that the order goes through from creation to archiving.
                    A bunch of standard steps have been included in the e-commerce module,
                    but this is also a place where you can add / remove your own customisations (steps) as required by your individual project.',

                'number_of_days_to_send_update_email' =>
                    'The maximum number of days available to send an status update for the customer for the specific order step',
            ],

            OrderStepConfirmed::class => [

                'list_of_things_to_check' =>
                    'One of the steps in the order steps sequence is the Order Confirmation.
                    This is when the Shop Admin looks at all the detail in the order and confirms it is ready to be completed.
                    Here you can create an HTML list of items to check (e.g. has it been paid, do you have the products in stock,
                    is there a delivery address,
                    etc....)',
            ],

            OrderStatusLog::class => [

                'available_log_classes_array' =>
                    'Tells us what order log classes are to be used.
                    OrderStatusLogSubmitted should always be used and does not need to be listed here.',

                'order_status_log_class_used_for_submitting_order' =>
                    'This is the log class used to record the submission of the order.
                    It is crucial to set this to the right log class, as a lot of the functionality in e-commerce depends on it: ',
            ],

            OrderStatusLogForm::class => [

                'controller_class' =>
                    'The controller class is used for OrderStatusLogForm forms.',

                'validator_class' =>
                    'The validator class is used for OrderStatusLogForm forms.',
            ],

            OrderEmail::class => [

                'send_all_emails_plain' =>
                    'Should all the emails be send as plain text?  Not recommended.',

                'css_file_location' =>
                    'This is a really useful setting where you can specify the location for a css file that is \'injected\' into the customer emails. ',

                'copy_to_admin_for_all_emails' =>
                    'Send a copy to the shop administrator for every email sent?',
            ],

            ################### ORDER DETAILS #####################
            Order::class => [

                'modifiers' =>
                    'This is the single most important setting.
                    Here you determine what modifiers are being added to every order.
                     You can just add them as a non-associative array.
                     However, their order is important!',

                'minutes_an_order_can_be_viewed_without_logging_in' =>
                    'Orders can be viewed with the special retrieve link (without the need for the user to log in) for xxx number of minutes.',

                'maximum_ignorable_sales_payments_difference' =>
                    'The maximum allowable difference between the Order Total and the Payment Total.
                    If this value is, for example, 10 cents and the total amount outstanding for an order is less than ten cents, than the order is considered \'paid\'',

                'order_id_start_number' =>
                    'The starting number for the order number.
                    For example, if you enter 1000 here then the first order will have number 1000,
                    the next one 1001 and so on.',

                'template_id_prefix' =>
                    'If you end up with conflicts in your templates (e.g. having the same ID twice)
                    then you can use this variable to set an prefix to all PHP generated IDs in all templates.
                    We use these PHP generated IDs for AJAX templates - where HTML, JS and PHP need to work together.',

                'ajax_subtotal_format' =>
                    'This is used when AJAX returns some values to update on the checkout page.
                    Specify which function returns the SubTotal value. You can also specify if you want a format to be called on that function.',

                'ajax_total_format' =>
                    'This is used when AJAX returns some values to update on the checkout page.
                    Specify which function returns the Total value. You can also specify if you want a format to be called on that function.',

                'date_format_for_title' =>
                    'Date format to show date in the title of the order e.g. see http://userguide.icu-project.org/formatparse/datetime for details
                    leave blank to exclude date format.',

                'include_customer_name_in_title' =>
                    'Include the name of the customer in the title of the order.  Set to false to exclude the name of the customer in the order title.',
            ],

            OrderItem::class => [

                'ajax_total_format' =>
                    'This is used when AJAX returns some values to update on the checkout page.
                    Specify which function returns the Total value.
                    You can also specify if you want a format to be called on that function.',
            ],

            OrderModifier::class => [

                'ajax_total_format' =>
                    'This is used when AJAX returns some values to update on the checkout page.
                    Specify which function returns the Total value.
                    You can also specify if you want a format to be called on that function.',
            ],

            ################### CUSTOMERS #####################
            OrderAddress::class => [

                'use_separate_shipping_address' =>
                    'Do the goods need to get shipped and if so,
                    do we allow these goods to be shipped to a different address than the billing address?',

                'use_shipping_address_for_main_region_and_country' =>
                    'In determing the country/region from which the order originated. For,
                    for example,
                    tax purposes - we use the Billing Address (@see Order::Country). However,
                    we can also choose the Shipping Address by setting this variable to TRUE.',

                'field_class_and_id_prefix' =>
                    'In case you have some conflicts in the class / IDs for formfields
                    then you can use this variable to add a few characters in front of the classes / IDs',
            ],

            EcommerceRole::class => [

                'permission_category' =>
                    'E-commerce permission group name.',

                'allow_customers_to_setup_accounts' =>
                    'Allow customers to become members when they purchase items.
                    If this is false then customers can never setup an account.
                    Orders will still get a member assigned to them but the member does not log in and they are not prompted for a password.',

                'must_have_account_to_purchase' =>
                    'When this is set to TRUE,
                    any purchasers must log in or create an account. When set to false,
                    customers still get added as a member,
                    but they can purchase without logging in or choosing a password.',

                'automatically_update_member_details' =>
                'When set to true,
                    the member fields (e.g. email,
                    surname,
                    first name) will be automatically updated from the billing address.  That is,
                    if the customers enters a different email or surname in the billing field then the member record will be updated based on these new values.',

                'customer_group_code' =>
                    'Code for the customer member group.',

                'customer_group_name' =>
                    'Title (name) for the customer member group.',

                'customer_permission_code' =>
                    'Permission code for the customer member group.',

                'admin_group_code' =>
                    'Code for the shop administrator member group.',

                'admin_group_name' =>
                    'Title (name) for the shop administrator member group.',

                'admin_group_user_first_name' =>
                    'First name for the shop administrator (e.g. John).',

                'admin_group_user_surname' =>
                    'Last name for the shop administrator (e.g. Smith).',

                'admin_group_user_email' =>
                    'Email address for the shop administrator (e.g. johnsmith@app.co.nz).',

                'admin_permission_code' =>
                    'Permission code for the shop administrator member group.',

                'admin_role_title' =>
                    'Role title for the shop administrator member group.',

                'admin_role_permission_codes' =>
                    'Permission codes for the shop administrator member group.',

                'assistant_group_code' =>
                    'Code for the shop assistant member group.',

                'assistant_group_name' =>
                    'Title (name) for the shop assistant member group.',

                'assistant_group_user_first_name' =>
                    'First name for the shop assistant (e.g. John).',

                'assistant_group_user_surname' =>
                    'Last name for the shop assistant (e.g. Smith).',

                'assistant_group_user_email' =>
                    'Email address for the shop assistant (e.g. johnsmith@app.co.nz).',

                'assistant_permission_code' =>
                    'Permission code for the shop assistant member group.',

                'assistant_role_title' =>
                    'Role title for the shop assistant member group.',

                'assistant_role_permission_codes' =>
                    'Permission codes for the shop assistant member group.',

                'process_orders_permission_code' =>
                    'Permission code for being allowed to process orders.
                    This code is separate from admins and assistants to make it easier to apply separate codes to groups.',
            ],

            BillingAddress::class => [

                'allow_selection_of_previous_addresses_in_checkout' =>
                    'In the checkout,  a customer to select from previously used addresses.',
                'required_fields' =>
                    'List of fields that is required to be entered.',
                'fields_to_google_geocode_conversion' =>
                    'This variable tells us how Billing Fields map to the Google Geo Coding objects.
                    If you set it to null or an empty array then there will be no geocoding.
                    See https://developers.google.com/maps/documentation/geocoding/#Types for more information.',
            ],

            ShippingAddress::class => [

                'allow_selection_of_previous_addresses_in_checkout' =>
                    'In the checkout, allow a customer to select from previously used addresses.',

                'required_fields' =>
                    'List of fields that is required to be entered.',

                'fields_to_google_geocode_conversion' =>
                    'This variable tells us how Shipping Fields map to the Google Geo Coding objects.
                    If you set it to null or an empty array then there will be no geocoding.
                    See https://developers.google.com/maps/documentation/geocoding/#Types for more information.',
            ],

            EcommerceCountry::class => [

                'allowed_country_codes' =>
                    'To what countries are you selling?  You can leave this as an empty array,
                    in case you are selling to all countries or you can restrict it to just one country or a handful.  Once set,
                    you can adjust this list in EcommerceCountry using the CMS. ',
                'visitor_country_provider' => 'The class that is being used to provide the country of the customer. Usually this is GEOIP,
                    but you can also setup your own one. This class just needs one public method: getCountry.',

                'default_country_code' =>
                    'The default country code (e.g. NZ or CA or UK). ',
            ],

            EcommerceRegion::class => [

                'visitor_region_provider' =>
                    'The class that is being used to provide the region of the customer. It is sort of like a GEOIP for regions.',

                'show_freetext_region_field' =>
                    'Provide a free text region field if no regions are specified. Region can also be \'State\',
                    or \'Province\', etc...',
            ],

            ################### PAYMENT AND MONEY #####################
            EcommerceCurrency::class => [

                'default_currency' =>
                    'The default currency used on the site.',

                'exchange_provider_class' =>
                    'The name of the class used to provide currency exchange rate....
                    You can easily built your own class here that can either provide fixed rates,
                    database stored rates or dynamic rates.',
            ],

            EcommerceMoney::class => [

                'default_format' =>
                    'Here you specify which function you want to be called as the default format for a Money object on the all site.',
            ],

            EcommercePayment::class => [

                'supported_methods' =>
                    'Associative array of payment methods,
                        e.g. ChequePayment: pay by cheque,
                        CreditCardPayment: pay by credit card,
                        etc....',
            ],

            ExpiryDateField::class => [
                'short_months' => 'Should we use short codes for the Expiry Date Field (e.g. Jan rather than January)?',
            ],

            ################### CLEANUP AND OTHER TASKS #####################
            EcommerceTaskCartCleanup::class => [

                'clear_minutes' =>
                        'The number of minutes after which carts are considered abandonned. If set to zero,
                        all objects will be cleared. If set to ten,
                        objects older than ten minutes will be cleared.',

                'clear_minutes_empty_carts' =>
                    'The number of minutes after which empty carts should be deleted (to reduce the amount of empty (meaningless) carts in the database).
                    If set to zero, all objects will be cleared.
                    If set to ten, objects older than ten minutes will be cleared.',

                'maximum_number_of_objects_deleted' =>
                    'This sets the total number of objects to be cleaned per clean.
                    We can keep this low to reduce time per clean and to reduce risks.',

                'never_delete_if_linked_to_member' =>
                    'If set to TRUE, then orders with a member linked to it will never be deleted.',

                'one_to_one_classes' =>
                    'An array of key / value pairs that are linked to orders as one-to-one relationships.
                    The key is the order field name (e.g. BillingAddressID) and the value is the class name (e.g. BillingAddress)',

                'one_to_many_classes' =>
                    'An array of key / value pairs that are linked to orders as one-to-many relationships.
                     The key is the class with the order ID and the value is the class name with the LastEdited field.',

                'many_to_many_classes' =>
                    'An array of key / value pairs that are linked to orders as many-to-many relationships.
                    Currently not in use.',

            ],
        ];

        //add more stuff through extensions
        $extendedArray = $this->extend(
            'moreDefinitions',
            $array
        );
        if ($extendedArray !== null && is_array($extendedArray) && count($extendedArray)) {
            foreach ($extendedArray as $extendedLabelsUpdate) {
                $array = array_merge(
                    $array,
                    $extendedLabelsUpdate
                );
            }
        }
        //add more stuff through child classes
        $childClasses = ClassInfo::subclassesFor(static::class);
        if (is_array($childClasses) && count($childClasses)) {
            foreach ($childClasses as $class) {
                if ($class !== static::class) {
                    $childObject = new $class();
                    $array = array_merge(
                        $array,
                        $childObject->Definitions()
                    );
                }
            }
        }
        //return what is appropriate
        if ($className && $variable) {
            return $array[$className][$variable];
        } elseif ($className) {
            return $array[$className];
        }
        return $array;
    }
}
