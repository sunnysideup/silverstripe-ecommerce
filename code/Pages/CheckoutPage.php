<?php

/**
 * CheckoutPage is a CMS page-type that shows the order
 * details to the customer for their current shopping
 * cart on the site. It also lets the customer review
 * the items in their cart, and manipulate them (add more,
 * deduct or remove items completely). The most important
 * thing is that the {@link CheckoutPage_Controller} handles
 * the {@link OrderForm} form instance, allowing the customer
 * to fill out their shipping details, confirming their order
 * and making a payment.
 *
 * @see CheckoutPage_Controller->Order()
 * @see OrderForm
 * @see CheckoutPage_Controller->OrderForm()
 *
 * The CheckoutPage_Controller is also responsible for setting
 * up the modifier forms for each of the OrderModifiers that are
 * enabled on the site (if applicable - some don't require a form
 * for user input). A usual implementation of a modifier form would
 * be something like allowing the customer to enter a discount code
 * so they can receive a discount on their order.
 * @see OrderModifier
 * @see CheckoutPage_Controller->ModifierForms()
 *
 * TO DO: get rid of all the messages...
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages

 **/
class CheckoutPage extends CartPage
{
    /**
     * standard SS variable.
     *
     * @Var Boolean
     */
    private static $hide_ancestor = 'CartPage';

    /**
     * standard SS variable.
     *
     * @Var string
     */
    private static $icon = 'ecommerce/images/icons/CheckoutPage';

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $db = [
        'TermsAndConditionsMessage' => 'Varchar(200)',
        'EnableGoogleAnalytics' => 'Boolean(1)',
    ];

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $has_one = [
        'TermsPage' => 'Page',
    ];

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $defaults = [
        'TermsAndConditionsMessage' => 'You must agree with the terms and conditions before proceeding.',
    ];

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Checkout Page';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Checkout Pages';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page where the customer can view the current order (cart) and finalise (submit) the order. Every e-commerce site needs an Order Confirmation Page.';

    public function i18n_singular_name()
    {
        return _t('CheckoutPage.SINGULARNAME', 'Checkout Page');
    }

    public function i18n_plural_name()
    {
        return _t('CheckoutPage.PLURALNAME', 'Checkout Pages');
    }

    /**
     * Returns the Terms and Conditions Page (if there is one).
     *
     * @return Page | NULL
     */
    public static function find_terms_and_conditions_page()
    {
        $checkoutPage = DataObject::get_one('CheckoutPage');
        if ($checkoutPage && $checkoutPage->TermsPageID) {
            return Page::get()->byID($checkoutPage->TermsPageID);
        }
    }

    /**
     * Returns the link or the Link to the Checkout page on this site.
     *
     * @param string $action [optional]
     *
     * @return string (URLSegment)
     */
    public static function find_link($action = null)
    {
        $page = DataObject::get_one('CheckoutPage');
        if ($page) {
            return $page->Link($action);
        }
        user_error('No Checkout Page has been created - it is recommended that you create this page type for correct functioning of E-commerce.', E_USER_NOTICE);

        return '';
    }

    /**
     * Returns the link or the Link to the Checkout page on this site
     * for the last step.
     *
     * @param string $step
     *
     * @return string (URLSegment)
     */
    public static function find_last_step_link($step = '')
    {
        if (! $step) {
            $steps = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
            if ($steps && count($steps)) {
                $step = array_pop($steps);
            }
        }
        if ($step) {
            $step = 'checkoutstep/' . strtolower($step) . '/#' . $step;
        }

        return self::find_link($step);
    }

    /**
     * Returns the link to the next step.
     *
     * @param string - $currentStep       is the step that has just been actioned....
     * @param bool -   $doPreviousInstead - return previous rather than next step
     *
     * @return string (URLSegment)
     */
    public static function find_next_step_link($currentStep, $doPreviousInstead = false)
    {
        $nextStep = null;
        if ($link = self::find_link()) {
            $steps = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
            if (in_array($currentStep, $steps, true)) {
                $key = array_search($currentStep, $steps, true);
                if ($key !== false) {
                    if ($doPreviousInstead) {
                        --$key;
                    } else {
                        ++$key;
                    }
                    if (isset($steps[$key])) {
                        $nextStep = $steps[$key];
                    }
                }
            } else {
                if ($doPreviousInstead) {
                    $nextStep = array_shift($steps);
                } else {
                    $nextStep = array_pop($steps);
                }
            }
            if ($nextStep) {
                return $link . 'checkoutstep' . '/' . $nextStep . '/';
            }

            return $link;
        }

        return '';
    }

    /**
     * Returns the link to the checkout page on this site, using
     * a specific Order ID that already exists in the database.
     *
     * @param int $orderID ID of the {@link Order}
     *
     * @return string Link to checkout page
     */
    public static function get_checkout_order_link($orderID)
    {
        if ($page = self::find_link()) {
            return $page->Link('showorder') . '/' . $orderID . '/';
        }

        return '';
    }

    /**
     * Standard SS function, we only allow for one checkout page to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canCreate($member = null)
    {
        return CheckoutPage::get()->Filter(['ClassName' => 'CheckoutPage'])->Count() ? false : $this->canEdit($member);
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
        return false;
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
     * Standard SS function.
     *
     * @return FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent :: getCMSFields();
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'ProceedToCheckoutLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'ContinueShoppingLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'ContinuePageID');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'LoadOrderLinkLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'CurrentOrderLinkLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'SaveOrderLinkLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'DeleteOrderLinkLabel');
        $termsPageIDField = OptionalTreeDropdownField::create(
            'TermsPageID',
            _t('CheckoutPage.TERMSANDCONDITIONSPAGE', 'Terms and conditions page'),
            'SiteTree'
        );
        $termsPageIDField->setRightTitle(_t('CheckoutPage.TERMSANDCONDITIONSPAGE_RIGHT', 'This is optional. To remove this page clear the reminder message below.'));
        $fields->addFieldToTab('Root.Terms', $termsPageIDField);
        $fields->addFieldToTab(
            'Root.Terms',
            $termsPageIDFieldMessage = new TextField(
                'TermsAndConditionsMessage',
                _t('CheckoutPage.TERMSANDCONDITIONSMESSAGE', 'Reminder Message')
            )
        );
        $termsPageIDFieldMessage->setRightTitle(
            _t('CheckoutPage.TERMSANDCONDITIONSMESSAGE_RIGHT', "Shown if the user does not tick the 'I agree with the Terms and Conditions' box. Leave blank to allow customer to proceed without ticking this box")
        );
        //The Content field has a slightly different meaning for the Checkout Page.
        $fields->removeFieldFromTab('Root.Main', 'Content');
        $fields->addFieldToTab('Root.Messages.Messages.AlwaysVisible', $htmlEditorField = new HTMLEditorField('Content', _t('CheckoutPage.CONTENT', 'General note - always visible on the checkout page')));
        $htmlEditorField->setRows(3);
        if (OrderModifierDescriptor::get()->count()) {
            $fields->addFieldToTab('Root.Messages.Messages.OrderExtras', $this->getOrderModifierDescriptionField());
        }
        if (CheckoutPageStepDescription::get()->count()) {
            $fields->addFieldToTab('Root.Messages.Messages.CheckoutSteps', $this->getCheckoutStepDescriptionField());
        }
        $fields->addFieldToTab('Root.Analytics', CheckboxField::create('EnableGoogleAnalytics', 'Enable E-commerce Google Analytics.  Make sure it is turned on in your Google Analytics account.'));

        return $fields;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (SiteTree::config()->create_default_pages) {
            $checkoutPage = DataObject::get_one('CheckoutPage');
            if (! $checkoutPage) {
                $checkoutPage = self::create();
                $checkoutPage->Title = 'Checkout';
                $checkoutPage->MenuTitle = 'Checkout';
                $checkoutPage->URLSegment = 'checkout';
                $checkoutPage->writeToStage('Stage');
                $checkoutPage->publish('Stage', 'Live');
            }
        }
    }

    /**
     * @return GridField
     */
    protected function getOrderModifierDescriptionField()
    {
        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(),
            new GridFieldSortableHeader(),
            new GridFieldDataColumns(),
            new GridFieldEditButton(),
            new GridFieldDetailForm()
        );
        $title = _t('CheckoutPage.ORDERMODIFIERDESCRIPTMESSAGES', 'Messages relating to order form extras (e.g. tax or shipping)');
        $source = OrderModifierDescriptor::get();

        return new GridField('OrderModifierDescriptor', $title, $source, $gridFieldConfig);
    }

    /**
     * @return GridField
     */
    protected function getCheckoutStepDescriptionField()
    {
        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(),
            new GridFieldSortableHeader(),
            new GridFieldDataColumns(),
            new GridFieldEditButton(),
            new GridFieldDetailForm()
        );
        $title = _t('CheckoutPage.CHECKOUTSTEPESCRIPTIONS', 'Checkout Step Descriptions');
        $source = CheckoutPageStepDescription::get();

        return new GridField('CheckoutPageStepDescription', $title, $source, $gridFieldConfig);
    }
}
