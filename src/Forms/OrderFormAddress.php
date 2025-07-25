<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Validation\OrderFormAddressValidator;
use Sunnysideup\Ecommerce\Forms\Validation\ShopAccountFormPasswordValidator;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\OrderAddress;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;

/**
 * This class is the form for editing the Order Addresses.
 * It is also used to link the order to a member.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class OrderFormAddress extends Form
{
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var array
     */
    protected $debugArray = [];

    /**
     * the member attached to the order
     * this is not always the same as the loggedInMember.
     *
     * @var object (Member)
     */
    protected $orderMember;

    /**
     * the logged in member, if any
     * this is not always the same as the orderMember.
     *
     * @var object (Member)
     */
    protected $loggedInMember;

    /**
     * ID of the member that has just been created.
     *
     * @var int
     */
    protected $newlyCreatedMemberID = 0;

    /**
     * ID of the member that has just been created.
     *
     * @var Order
     */
    protected $order;

    /**
     * @var bool
     */
    private static $shipping_address_first = true;

    /**
     * @param string $name
     */
    public function __construct(Controller $controller, $name)
    {
        //set basics
        $requiredFields = [];
        $shippingAddress = null;

        //requirements
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomOrderFormAddress.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
        if (EcommerceConfig::get(OrderAddress::class, 'use_separate_shipping_address')) {
            Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomOrderFormShipping.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
        }

        //  ________________ 1) Order + Member + Address fields

        // define field lists ...
        $addressFieldsMember = FieldList::create();
        $addressFieldsBilling = FieldList::create();
        $addressFieldsShipping = null;
        $useShippingAddressField = null;
        $shippingAddressFirst = EcommerceConfig::get(OrderFormAddress::class, 'shipping_address_first');

        $addressFieldsMember->push(
            HeaderField::create(
                'AddressFieldsMemberHeading',
                _t('OrderFormAddress.Address_Fields_Member_Heading', 'Your Personal Details'),
                2
            )
        );
        //find member
        $this->order = ShoppingCart::current_order();
        $this->orderMember = $this->order->CreateOrReturnExistingMember(false);
        $this->loggedInMember = Security::getCurrentUser();

        //strange security situation...
        if ($this->orderMember->exists() && $this->loggedInMember) {
            if ($this->orderMember->ID !== $this->loggedInMember->ID) {
                if (! $this->loggedInMember->IsShopAdmin()) {
                    $this->loggedInMember->logOut();
                }
            }
        }

        // member fields
        if ($this->orderMember) {
            $memberFields = $this->orderMember->getEcommerceFields();
            $requiredFields = array_merge($requiredFields, $this->orderMember->getEcommerceRequiredFields());
            if ($this->loggedInMember) {
                $memberFields->replaceField('Email', ReadonlyField::create('Email', 'Email', $this->loggedInMember->Email));
            } else {
                $memberFields->dataFieldByName('Email')
                    ->setAttribute('data-login-link', $controller->SendLoginLinkLink())
                    ->setAttribute('data-security-token', SecurityToken::inst()->getValue());
            }
            $addressFieldsMember->merge($memberFields);
        }

        //billing address field
        $billingAddress = $this->order->CreateOrReturnExistingAddress(BillingAddress::class);
        $billingAddressFields = $billingAddress->getFields($this->orderMember);
        $addressFieldsBilling->merge($billingAddressFields);

        $requiredFields = array_merge($requiredFields, $billingAddress->getRequiredFields());

        // remove email field if member is logged in as they can not change it here!
        if ($this->loggedInMember) {
            $requiredFields = array_values(array_diff($requiredFields, ['Email']));
        }
        //HACK: move phone to member fields ..
        if ($addressFieldsMember) {
            if ($addressFieldsBilling) {
                $phoneField = $addressFieldsBilling->dataFieldByName('Phone');
                if ($phoneField) {
                    $addressFieldsBilling->removeByName('Phone');
                    $addressFieldsMember->insertAfter('Email', $phoneField);
                }
            }
        }

        //shipping address field

        if (EcommerceConfig::get(OrderAddress::class, 'use_separate_shipping_address')) {
            //add the important CHECKBOX
            $useShippingAddressField = FieldList::create(
                HeaderField::create(
                    'HasShippingAddressHeader',
                    _t('OrderFormAddress.HAS_SHIPPING_ADDRESS_HEADER', 'Delivery Option'),
                    2
                )
            );
            $useShippingAddress = $this->order ? $this->order->UseShippingAddress : 0;
            if ($shippingAddressFirst) {
                $useShippingAddressField->push(
                    CheckboxField::create(
                        'UseDifferentShippingAddress',
                        _t('OrderForm.USE_DIFFERENT_SHIPPING_ADDRESS', 'I need to enter a separate billing address'),
                        $useShippingAddress
                    )
                );
                $useShippingAddressField->push(
                    HiddenField::create('UseShippingAddress', 'UseShippingAddress', $useShippingAddress)
                );
            } else {
                $useShippingAddressField->push(
                    CheckboxField::create(
                        'UseShippingAddress',
                        _t('OrderForm.USESHIPPINGADDRESS', 'Use separate shipping address'),
                        $useShippingAddress
                    )
                );
            }

            $addressFieldsShipping = FieldList::create();

            //$addressFieldsShipping->merge($useShippingAddressField);
            //now we can add the shipping fields
            $shippingAddress = $this->order->CreateOrReturnExistingAddress(ShippingAddress::class);
            $shippingAddressFields = $shippingAddress->getFields($this->orderMember);
            $requiredFields = array_merge($requiredFields, $shippingAddress->getRequiredFields());
            $addressFieldsShipping->merge($shippingAddressFields);
        }

        //create holder
        $allLeftFields = CompositeField::create();
        $allLeftFields->addExtraClass('leftOrder');

        //member fields holder
        $leftFieldsMember = CompositeField::create($addressFieldsMember);
        $leftFieldsMember->addExtraClass('leftOrderMember');

        //creating shipping fields holder
        $leftFieldsShipping = CompositeField::create($addressFieldsShipping)->setName('ShippingFields');
        $leftFieldsShipping->addExtraClass('leftOrderShipping');

        //creating billing fields holder
        $leftFieldsBilling = CompositeField::create($addressFieldsBilling)->setName('BillingFields');
        $leftFieldsBilling->addExtraClass('leftOrderBilling');

        //adding member fields ...
        $allLeftFields->push($leftFieldsMember);
        if ($useShippingAddressField) {
            $leftFieldsShippingOptions = CompositeField::create($useShippingAddressField)->setName('ShippingAddressSelector');
            $leftFieldsShippingOptions->addExtraClass('leftOrderShippingOptions');
            $allLeftFields->push($leftFieldsShippingOptions);
        }
        if ($shippingAddressFirst) {
            if ($addressFieldsShipping) {
                $allLeftFields->push($leftFieldsShipping);
            }
            $allLeftFields->push($leftFieldsBilling);
        } else {
            $allLeftFields->push($leftFieldsBilling);
            if ($addressFieldsShipping) {
                $allLeftFields->push($leftFieldsShipping);
            }
        }

        //  ________________  2) Log in / vs Create Account fields - RIGHT-HAND-SIDE fields

        $rightFields = CompositeField::create();
        $rightFields->addExtraClass('rightOrder');
        //to do: simplify
        if (EcommerceConfig::get(EcommerceRole::class, 'allow_customers_to_setup_accounts')) {
            if ($this->orderDoesNotHaveFullyOperationalMember()) {
                //general header
                if (! $this->loggedInMember) {
                    $rightFields->push(
                        //TODO: check EXACT link!!!
                        new LiteralField('MemberInfo', '<p class="message good">' . _t('OrderForm.MEMBERINFO', 'If you already have an account then please') . ' <a href="Security/login/?BackURL=/' . urlencode(implode('/', $controller->getURLParams())) . '">' . _t('OrderForm.LOGIN', 'log in') . '</a>.</p>')
                    );
                }
            } elseif ($this->loggedInMember) {
                $rightFields->push(
                    new LiteralField(
                        'LoginNote',
                        '<p class="message good">' . _t('Account.LOGGEDIN', 'You are logged in as ') .
                            Convert::raw2xml($this->loggedInMember->FirstName) . ' ' .
                            Convert::raw2xml($this->loggedInMember->Surname) .
                            ' (' . Convert::raw2xml($this->loggedInMember->Email) . ').' .
                            ' <a href="/Security/logout/">' .
                            _t('Account.LOGOUTNOW', 'Log out?') .
                            '</a>' .
                            '</p>'
                    )
                );
            }
            if ($this->orderMember->exists()) {
                if ($this->loggedInMember) {
                    if ($this->loggedInMember->ID !== $this->orderMember->ID) {
                        $rightFields->push(
                            new LiteralField(
                                'OrderAddedTo',
                                '<p class="message good">' .
                                    _t('Account.ORDERADDEDTO', 'Order will be added to ') .
                                    Convert::raw2xml($this->orderMember->Email) .
                                    ').</p>'
                            )
                        );
                    }
                }
            }
        }

        //  ________________  5) Put all the fields in one FieldList

        $fields = FieldList::create($rightFields, $allLeftFields);

        //  ________________  6) Actions and required fields creation + Final Form construction

        $nextButton = new FormAction('saveAddress', _t('OrderForm.NEXT', 'Next'));
        $nextButton->addExtraClass('next');

        $actions = FieldList::create($nextButton);

        $validator = OrderFormAddressValidator::create($requiredFields);

        parent::__construct($controller, $name, $fields, $actions, $validator);
        $this->setAttribute('autocomplete', 'off');
        //extensions need to be set after __construct
        //extension point
        $this->extend('updateFields', $fields);
        $this->setFields($fields);
        $this->extend('updateActions', $actions);
        $this->setActions($actions);
        $this->extend('updateValidator', $validator);
        $this->setValidator($validator);

        //this needs to come after the extension calls
        foreach ($validator->getRequired() as $requiredField) {
            $field = $fields->dataFieldByName($requiredField);
            if ($field) {
                $field->addExtraClass('required');
            }
        }

        //  ________________  7)  Load saved data

        //we do this first so that Billing and Shipping Address can override this...

        if ($this->orderMember) {
            $this->loadDataFrom($this->orderMember);
        }
        if ($this->order) {
            $this->loadDataFrom($this->order);
            if ($billingAddress) {
                $this->loadDataFrom($billingAddress);
            }
            if (EcommerceConfig::get(OrderAddress::class, 'use_separate_shipping_address')) {
                if ($shippingAddress) {
                    $this->loadDataFrom($shippingAddress);
                }
            }
        }
        $fields->dataFieldByName('FirstName')->setValue($this->orderMember->FirstName);
        $fields->dataFieldByName('Surname')->setValue($this->orderMember->Surname);
        $fields->dataFieldByName('Email')->setValue($this->orderMember->Email);


        //allow updating via decoration
        $oldData = Controller::curr()->getRequest()->getSession()->get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }

        $this->extend('updateOrderFormAddress', $this);
    }

    /**
     * Process the items in the shopping cart from session,
     * creating a new {@link Order} record, and updating the
     * customer's details {@link Member} record.
     *
     * {@link Payment} instance is created, linked to the order,
     * and payment is processed {@link Payment::processPayment()}
     *
     * @param array $data Form request data submitted from OrderForm
     * @param Form  $form Form object for this action
     *
     * @return \SilverStripe\Control\HTTPRequest Request object for this action
     */
    public function saveAddress(array $data, Form $form, HTTPRequest $request)
    {
        $outcome = $this->saveAddressDetails($data, $form, $request);
        if ($outcome) {
            $nextStepLink = CheckoutPage::find_next_step_link('orderformaddress');
            $this->controller->redirect($nextStepLink);

            return true;
        }
    }

    /**
     * Process the items in the shopping cart from session,
     * creating a new {@link Order} record, and updating the
     * customer's details {@link Member} record.
     *
     * {@link Payment} instance is created, linked to the order,
     * and payment is processed {@link Payment::processPayment()}
     *
     * @param array $data Form request data submitted from OrderForm
     * @param Form  $form Form object for this action
     *
     * @return \SilverStripe\Control\HTTPRequest Request object for this action
     */
    public function saveAddressDetails(array $data, Form $form, HTTPRequest $request)
    {
        Controller::curr()->getRequest()->getSession()->set('BillingEcommerceGeocodingFieldValue', empty($data['BillingEcommerceGeocodingField']) ? null : $data['BillingEcommerceGeocodingField']);
        Controller::curr()->getRequest()->getSession()->set('ShippingEcommerceGeocodingFieldValue', empty($data['ShippingEcommerceGeocodingField']) ? null : $data['ShippingEcommerceGeocodingField']);
        $this->saveDataToSession();

        $data = Convert::raw2sql($data);
        //check for cart items
        if (! $this->order) {
            $form->sessionMessage(_t('OrderForm.ORDERNOTFOUND', 'Your order could not be found.'), 'bad');
            $this->controller->redirectBack();

            return false;
        }
        //recalculated = true... in TotalItems
        if ($this->order && 0 === (int) $this->order->TotalItems(true)) {
            // WE DO NOT NEED THE THING BELOW BECAUSE IT IS ALREADY IN THE TEMPLATE AND IT CAN LEAD TO SHOWING ORDER WITH ITEMS AND MESSAGE
            $form->sessionMessage(_t('OrderForm.NOITEMSINCART', 'Please add some items to your cart.'), 'bad');
            $this->controller->redirectBack();

            return false;
        }

        //----------- START BY SAVING INTO ORDER
        $form->saveInto($this->order);
        //----------- --------------------------------

        //MEMBER
        $this->orderMember = $this->createOrFindMember($data);
        if ($this->debug) {
            //debug::log('debug array from OrderFormAddress:'.implode("\r\n<hr />", $this->debugArray));
        }

        if ($this->orderMember && is_object($this->orderMember)) {
            if ($this->memberShouldBeSaved($data)) {
                $form->saveInto($this->orderMember);
                $password = $this->validPasswordHasBeenEntered($data);
                if ($password) {
                    $this->orderMember->changePassword($password);
                }
                $this->orderMember->write();
            }
            if ($this->memberShouldBeLoggedIn($data)) {
                Injector::inst()->get(IdentityStore::class)->logIn($this->orderMember);
            }
            //this causes ERRORS ....
            $this->order->MemberID = $this->orderMember->ID;

            Controller::curr()->getRequest()->getSession()->set('Ecommerce_Member_For_Order', $this->orderMember->ID);
        }

        //BILLING ADDRESS
        $billingAddress = $this->order->CreateOrReturnExistingAddress(BillingAddress::class);
        if ($billingAddress) {
            $form->saveInto($billingAddress);
            // NOTE: write should return the new ID of the object
            $this->order->BillingAddressID = $billingAddress->write();
        }

        // SHIPPING ADDRESS
        if (isset($data['UseShippingAddress'])) {
            if ($data['UseShippingAddress']) {
                $shippingAddress = $this->order->CreateOrReturnExistingAddress(ShippingAddress::class);
                if ($shippingAddress) {
                    $form->saveInto($shippingAddress);
                    // NOTE: write should return the new ID of the object
                    $this->order->ShippingAddressID = $shippingAddress->write();
                }
            }
        }

        $this->extend('saveAddressExtension', $data, $form, $order, $this->orderMember);

        //SAVE ORDER
        $this->order->write();

        //----------------- CLEAR OLD DATA ------------------------------
        $this->clearSessionData(); //clears the stored session form data that might have been needed if validation failed
        //-----------------------------------------------
        return true;
    }

    /**
     * saves the form into session.
     */
    public function saveDataToSession()
    {
        $data = $this->getData();
        $data = Sanitizer::remove_from_data_array($data);

        $this->setSessionData($data);
    }

    /**
     * clear the form data (after the form has been submitted and processed).
     */
    public function clearSessionData()
    {
        $this->clearMessage();

        Controller::curr()->getRequest()->getSession()->set("FormInfo.{$this->FormName()}.data", null);
    }

    /**
     * returns TRUE if
     * - there is no existing member with the same value in the unique field
     * - OR the member is not logged in.
     * - OR the member is a Shop Admin (we assume they are placing an order on behalf of someone else).
     * returns FALSE if
     * - the unique field already exists in another member
     * - AND the member being "tested" is already logged in...
     * in that case the logged in member tries to take on another identity.
     * If you are not logged BUT the the unique field is used by an existing member then we can still
     * use the field - we just CAN NOT log in the member.
     * This method needs to be public because it is used by the OrderFormValidator (see below).
     *
     * @param array $data form data - should include $data[uniqueField....] - e.g. $data["Email"]
     *
     * @return bool
     */
    public function uniqueMemberFieldCanBeUsed(array $data)
    {
        if ($this->loggedInMember && $this->anotherExistingMemberWithSameUniqueFieldValue($data)) {
            //there is an exception for shop admins
            //who can place an order on behalve of a customer.
            if ($this->loggedInMember->IsShopAdmin()) {
                //REMOVED PART:
                //but NOT when the member placing the Order is the ShopAdmin
                //AND there is another member with the same credentials.
                //because in that case the ShopAdmin is not placing an order
                //on behalf of someone else.
                //that is,
                //if($this->orderMember->ID == $this->loggedInMember->ID) {
                //    return false;
                //}
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Is there a member that is fully operational?
     * - saved
     * - has password.
     */
    protected function orderHasFullyOperationalMember(): bool
    {
        //orderMember is Created in __CONSTRUCT
        if ($this->orderMember) {
            if ($this->orderMember->exists()) {
                if ($this->orderMember->Password) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Opposite of orderHasFullyOperationalMember method.
     *
     * @return bool
     */
    protected function orderDoesNotHaveFullyOperationalMember()
    {
        return ! $this->orderHasFullyOperationalMember();
    }

    /**
     * Works out the most likely member for the order after submission of the form.
     * It returns a member if appropriate.
     * 1. does the order already have a member that is not a shop-admin - if so - DONE.
     * 2. shop allows creation of member? - if NOT return NULL
     * A. is the logged in member the shop admin placing an order on behalf of someone else?
     * A1. is the email entered different from the admin email?
     * A2. attach to other member as new one or existing one.
     * 3. can the entered data be used? - if
     * 4. is there no member logged in yet? - If there is one return null, member is already linked to order.
     * 5. find member from data entered (even if not logged in)
     * 6. At this stage, if we dont have a member, we will create one!
     * 7. We do one last check to see if we are allowed to create one.
     *
     * @param array $data form data - should include $data[uniqueField....] - e.g. $data["Email"]
     *
     * @return null|\SilverStripe\Security\Member
     */
    protected function createOrFindMember(array $data)
    {
        //get the best available from order.
        $this->orderMember = $this->order->CreateOrReturnExistingMember(false);
        $orderPlacedByShopAdmin = $this->loggedInUserIsAdmin();
        //1. does the order already have a member
        if ($this->orderMember->exists() && ! $orderPlacedByShopAdmin) {
            if ($this->debug) {
                $this->debugArray[] = '1. the order already has a member';
            }
        } elseif ($orderPlacedByShopAdmin) {
            if ($this->debug) {
                $this->debugArray[] = 'A1. shop admin places order ';
            }
            //2. does email match shopadmin email
            $newEmail = $this->enteredEmailAddressDoesNotMatchLoggedInUser($data);
            if ($newEmail) {
                $this->orderMember = null;
                if ($this->debug) {
                    $this->debugArray[] = 'A2. email does not match shopadmin email - reset orderMember';
                }
                $this->orderMember = $this->anotherExistingMemberWithSameUniqueFieldValue($data);
                if ($this->orderMember) {
                    if ($this->debug) {
                        $this->debugArray[] = 'A3. the other member already exists';
                    }
                } elseif ($this->memberShouldBeCreated($data)) {
                    if ($this->debug) {
                        $this->debugArray[] = 'A4. No other member found - creating new one';
                    }
                    $this->orderMember = Member::create();
                    $this->orderMember->Email = Convert::raw2sql($newEmail);
                    $this->newlyCreatedMemberID = $this->orderMember->write();
                }
            }
        } else {
            if ($this->debug) {
                $this->debugArray[] = '2. shop allows creation of member';
            }
            $this->orderMember = null;

            //3. can the entered data be used?
            //member that will be added does not exist somewhere else.
            if ($this->uniqueMemberFieldCanBeUsed($data)) {
                if ($this->debug) {
                    $this->debugArray[] = '3. can the entered data be used?';
                }
                // 4. is there no member logged in yet?
                //no logged in member
                if (! $this->loggedInMember) {
                    if ($this->debug) {
                        $this->debugArray[] = '4. is there no member logged in yet?';
                    }
                    //5. find member from data entered (even if not logged in)
                    //another member with the same email?

                    if ($this->debug) {
                        $this->debugArray[] = '5. find member from data entered (even if not logged in)';
                    }
                    $this->orderMember = $this->anotherExistingMemberWithSameUniqueFieldValue($data);

                    //6. At this stage, if we dont have a member, we will create one!
                    //in case we still dont have a member AND we should create a member for every customer, then we do this below...
                    if (! $this->orderMember) {
                        if ($this->debug) {
                            $this->debugArray[] = '6. No other member found';
                        }
                        // 7. We do one last check to see if we are allowed to create one
                        //are we allowed to create a member?
                        if ($this->memberShouldBeCreated($data)) {
                            if ($this->debug) {
                                $this->debugArray[] = '7. We do one last check to see if we are allowed to create one. CREATE NEW MEMBER';
                            }
                            $this->orderMember = $this->order->CreateOrReturnExistingMember(false);
                            $this->orderMember->write();
                            //this is safe because of the memberShouldBeCreated above ...
                            $this->newlyCreatedMemberID = $this->orderMember->ID;
                        }
                    }
                }
            }
        }

        return $this->orderMember ?: null;
    }

    /**
     * Should a new member be created?
     *
     * @Todo: explain why password needs to be more than three characters...
     * @todo: create class that checks if password is good enough
     *
     * @param array $data form data - should include $data[uniqueField....] - e.g. $data["Email"]
     *
     * @return bool
     */
    protected function memberShouldBeCreated(array $data)
    {
        //shop admin and
        //data entered does not match shop admin and
        //data entered does not match existing member...
        //TRUE!
        if ($this->loggedInUserIsAdmin()) {
            if ($this->enteredEmailAddressDoesNotMatchLoggedInUser($data)) {
                return ! $this->anotherExistingMemberWithSameUniqueFieldValue($data);
            }
        } elseif ($this->loggedInMember || $this->newlyCreatedMemberID) {
            // already logged in or already created...
            // FALSE!
            return false;
        } else {
            // no other user exists with the email...
            // TRUE!
            return $this->anotherExistingMemberWithSameUniqueFieldValue($data) ? false : true;
        }
        //defaults to FALSE...
        return false;
    }

    protected function loggedInUserIsAdmin(): bool
    {
        return $this->loggedInMember && $this->loggedInMember->IsShopAdmin();
    }

    /**
     * @param array $data form data - should include $data[uniqueField....] - e.g. $data["Email"]
     *
     * @return bool
     */
    protected function memberShouldBeSaved(array $data)
    {

        // logged in member is shop admin and members are updateable...
        if ($this->loggedInUserIsAdmin()) {
            return false;
        }

        //new members always need to be saved
        $newMember = $this->memberShouldBeCreated($data) ||
            $this->newlyCreatedMemberID;

        if ($this->loggedInMember && !$this->orderMember) {
            return false;
        }
        if ($this->orderMemberAndLoggedInMemberAreDifferent()) {
            return false;
        }

        // existing logged in members need to be saved if they are updateable
        // AND do not match someone else...
        $updateableMember = $this->loggedInMember &&
            ! $this->anotherExistingMemberWithSameUniqueFieldValue($data) &&
            EcommerceConfig::get(EcommerceRole::class, 'automatically_update_member_details');

        return $newMember || $updateableMember;
    }

    /**
     * returns TRUE if
     * - the member is not logged in
     * - the member is new AND
     * - the password is valid.
     *
     * @param array $data form data - should include $data[uniqueField....] - e.g. $data["Email"]
     *
     * @return bool
     */
    protected function memberShouldBeLoggedIn(array $data)
    {
        if (! $this->loggedInMember) {
            if ($this->newlyCreatedMemberID > 0 && $this->validPasswordHasBeenEntered($data)) {
                return true;
            }
        }

        return false;
    }

    protected function orderMemberAndLoggedInMemberAreDifferent()
    {
        $this->loggedInMember && $this->orderMember && (int) $this->loggedInMember->ID !== (int) $this->orderMember->ID;
    }

    /**
     * returns existing member if it already exists and it is not the logged-in one.
     * Based on the unique field (email)).
     *
     * @param array $data form data - should include $data[uniqueField....] - e.g. $data["Email"]
     */
    protected function anotherExistingMemberWithSameUniqueFieldValue(array $data): ?Member
    {
        $uniqueFieldName = Member::config()->get('unique_identifier_field');
        //The check below covers both Scenario 3 and 4....
        if (isset($data[$uniqueFieldName])) {
            $currentUserID = $this->loggedInMember ? $this->loggedInMember->ID : 0;
            $uniqueFieldValue = $data[$uniqueFieldName];
            //no need to convert raw2sql as this has already been done.
            return Member::get()
                ->filter(
                    [
                        $uniqueFieldName => $uniqueFieldValue,
                    ]
                )
                ->exclude(
                    [
                        'ID' => $currentUserID,
                    ]
                )
                ->First()
            ;
        } else {
            if ($this->loggedInMember && $this->loggedInMember->Email) {
                // all good
            }
        }
        return null;
    }

    /**
     * returns the email if
     * - user is logged in already
     * - user's email in DB does not match email entered.
     *
     * @return false|string
     */
    protected function enteredEmailAddressDoesNotMatchLoggedInUser(array $data)
    {
        if ($this->loggedInMember) {
            $DBUniqueFieldName = $this->loggedInMember->Email;
            if ($DBUniqueFieldName) {
                $uniqueFieldName = Member::config()->get('unique_identifier_field');
                if (isset($data[$uniqueFieldName])) {
                    $enteredUniqueFieldName = $data[$uniqueFieldName];
                    if ($enteredUniqueFieldName) {
                        if ($DBUniqueFieldName !== $enteredUniqueFieldName) {
                            return $enteredUniqueFieldName;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if the password is good enough.
     *
     * @param array $data (from form)
     *
     * @return string
     */
    protected function validPasswordHasBeenEntered(array $data)
    {
        return ShopAccountFormPasswordValidator::clean_password($data);
    }
}
