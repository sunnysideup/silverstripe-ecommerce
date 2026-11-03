<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Forms\Validation\ShopAccountFormPasswordValidator;
use Sunnysideup\Ecommerce\Forms\Validation\ShopAccountFormValidator;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;

/**
 * @description: ShopAccountForm allows shop members to update their details.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class ShopAccountForm extends Form
{
    /**
     * @param Controller $controller
     * @param mixed      $name
     * @param mixed      $mustCreateAccount
     */
    public function __construct($controller, $name, $mustCreateAccount = false)
    {
        $member = Security::getCurrentUser();
        if ($member && $member->exists()) {
            $fields = $member->getEcommerceFields(false, true);
            $clearCartAndLogoutLink = ShoppingCartController::clear_cart_and_logout_link();
            $loginMessage =
                '<span class="customerName">' . trim(Convert::raw2xml($member->FirstName) . ' ' . Convert::raw2xml($member->Surname)) . '</span>, '
                . '<a href="' . $clearCartAndLogoutLink . '">' . _t('Account.LOGOUT', 'Log out now?') .
                '</a>';
            $loginField = HTMLReadonlyField::create(
                'LoggedInAsNote',
                _t('Account.LOGGEDIN', 'You are currently logged in as '),
                $loginMessage
            );
            $fields->push($loginField);
            if ($member->Password) {
                // needed to change email or password (checked in processForm)
                $fields->push(
                    PasswordField::create(
                        'CurrentPassword',
                        _t('Account.CURRENT_PASSWORD', 'Current password')
                    )->setDescription(_t('Account.CURRENT_PASSWORD_DESCRIPTION', 'Only needed if you want to change your email or password.'))
                );
            }
            $actions = new FieldList();
            $order = ShoppingCart::current_order();
            if ($order) {
                if ($order->getTotalItems()) {
                    $actions->push(new FormAction(
                        'proceed',
                        _t('Account.SAVE_AND_PROCEED', 'Save and review order')
                    ));
                } else {
                    $actions->push(new FormAction('submit', _t('Account.SAVE', 'Save Changes')));
                }
            }
        } else {
            $member = Member::create();
            $fields = new FieldList();
            $urlParams = $controller->getURLParams();
            $backURLLink = Director::baseURL();
            if ($urlParams) {
                foreach ($urlParams as $urlParam) {
                    if ($urlParam) {
                        $backURLLink = Controller::join_links($backURLLink, $urlParam);
                    }
                }
            }
            $backURLLink = urlencode($backURLLink);
            $fields->push(new LiteralField('MemberInfo', '<p class="message good">' . _t('OrderForm.MEMBERINFO', 'If you already have an account then please') . ' <a href="Security/login?BackURL=' . $backURLLink . '">' . _t('OrderForm.LOGIN', 'log in') . '</a>.</p>'));
            $memberFields = $member->getEcommerceFields($mustCreateAccount, true);
            if ($memberFields) {
                foreach ($memberFields as $memberField) {
                    $fields->push($memberField);
                }
            }
            $passwordField = new PasswordField('PasswordCheck1', _t('Account.PASSWORD', 'Password'));
            $passwordFieldCheck = new PasswordField('PasswordCheck2', _t('Account.PASSWORDCHECK', 'Password (repeat)'));
            $fields->push($passwordField);
            $fields->push($passwordFieldCheck);
            $actions = new FieldList(
                new FormAction('creatememberandaddtoorder', _t('Account.SAVE', 'Create Account'))
            );
        }

        $requiredFields = ShopAccountFormValidator::create($member->getEcommerceRequiredFields());
        parent::__construct($controller, $name, $fields, $actions, $requiredFields);
        $this->setAttribute('autocomplete', 'off');
        //extensions need to be set after __construct
        //extension point
        $this->extend('updateFields', $fields);
        $this->setFields($fields);
        $this->extend('updateActions', $actions);
        $this->setActions($actions);
        $this->extend('updateValidator', $requiredFields);
        $this->setValidator($requiredFields);

        if ($member) {
            $this->loadDataFrom($member);
        }

        $oldData = Controller::curr()->getRequest()->getSession()->get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateShopAccountForm', $this);
    }

    /**
     * Save the changes to the form, and go back to the account page.
     *
     * @param mixed $data
     * @param mixed $form
     * @param mixed $request
     *
     * @return bool + redirection
     */
    public function submit($data, $form, $request)
    {
        return $this->processForm($data, $form, $request);
    }

    /**
     * Save the changes to the form, and redirect to the checkout page.
     *
     * @param mixed $data
     * @param mixed $form
     * @param mixed $request
     *
     * @return bool + redirection
     */
    public function proceed($data, $form, $request)
    {
        return $this->processForm($data, $form, $request, CheckoutPage::find_link());
    }

    /**
     * create a member and add it to the order
     * then redirect back...
     *
     * @param array $data
     * @param Form  $form
     */
    public function creatememberandaddtoorder($data, $form)
    {
        $member = new Member();
        $order = ShoppingCart::current_order();
        if ($order && $order->exists()) {
            $form->saveInto($member);
            $password = ShopAccountFormPasswordValidator::clean_password($data);
            if ($password) {
                $passwordResult = $member->changePassword($password, false);
                if (! $passwordResult->isValid()) {
                    $this->sessionMessage(_t('Account.NO_VALID_PASSWORD', 'You need to enter a valid password.') . ' ' . ShopAccountFormPasswordValidator::messages_as_string($passwordResult), 'bad');
                } elseif ($member->validate()->isValid()) {
                    $member->write();
                    if ($member->exists()) {
                        if (! $order->MemberID) {
                            $order->MemberID = $member->ID;
                            $order->write();
                        }
                        Injector::inst()->get(IdentityStore::class)->logIn($member);
                        $this->sessionMessage(_t('ShopAccountForm.SAVEDDETAILS', 'Your details has been saved.'), 'good');
                    } else {
                        $this->sessionMessage(_t('ShopAccountForm.COULD_NOT_CREATE_RECORD', 'Could not save create a record for your details.'), 'bad');
                    }
                } else {
                    $this->sessionMessage(_t('ShopAccountForm.COULD_NOT_VALIDATE_MEMBER', 'Could not save your details.'), 'bad');
                }
            }
        } else {
            $this->sessionMessage(_t('ShopAccountForm.COULDNOTFINDORDER', 'Could not find order.'), 'bad');
        }
        $this->controller->redirectBack();
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
     * redirects ....
     *
     * @param mixed $form
     * @param mixed $request
     *
     * @return HTTPResponse
     */
    protected function processForm(array $data, $form, $request, ?string $link = '')
    {
        $member = Security::getCurrentUser();
        if (! $member || ! $member->exists()) {
            $form->sessionMessage(_t('Account.DETAILSNOTSAVED', 'Your details could not be saved.'), 'bad');

            return $this->controller->redirectBack();
        }
        $password = ShopAccountFormPasswordValidator::clean_password($data);
        if (! $password && ! empty($data['PasswordCheck1'])) {
            $form->sessionMessage(_t('Account.NO_VALID_PASSWORD', 'You need to enter a valid password.'), 'bad');

            return $this->controller->redirectBack();
        }
        // Changing the email or the password of an account that has a password
        // requires the current password.
        // (otherwise anyone with access to a logged-in browser can take over the account).
        // NB: this has to be checked BEFORE saving anything into the member.
        $newEmail = isset($data['Email']) && is_string($data['Email']) ? trim($data['Email']) : '';
        $emailChanged = '' !== $newEmail && 0 !== strcasecmp($newEmail, (string) $member->Email);
        // The validator skips the "email is taken" check for shop admins (so they can place orders for other people).
        // That is not right here: this form saves INTO the logged-in member, so the email must never belong to somebody else.
        if ($emailChanged) {
            $uniqueFieldName = Member::config()->get('unique_identifier_field');
            $taken = Member::get()
                ->filter([$uniqueFieldName => $newEmail])
                ->exclude(['ID' => $member->ID])
                ->exists()
            ;
            if ($taken) {
                $form->sessionMessage(
                    _t(
                        'Account.ALREADYTAKEN',
                        'This {uniqueFieldName} can not be used. If you already have an account, please log in. Otherwise, please use another {uniqueFieldName}.',
                        ['uniqueFieldName' => strtolower($uniqueFieldName)]
                    ),
                    'bad'
                );

                return $this->controller->redirectBack();
            }
        }
        if (($password || $emailChanged) && ! ShopAccountFormPasswordValidator::can_set_password_without_current_password($member)) {
            $currentPassword = isset($data['CurrentPassword']) && is_string($data['CurrentPassword']) ? $data['CurrentPassword'] : '';
            if (! ShopAccountFormPasswordValidator::is_current_password($member, $currentPassword)) {
                $form->sessionMessage(_t('Account.CURRENT_PASSWORD_REQUIRED', 'Please enter your current password to change your email or password.'), 'bad');

                return $this->controller->redirectBack();
            }
        }
        $form->saveInto($member);
        if ($password) {
            // do not write yet: we only write if the member as a whole is valid (see below).
            $passwordResult = $member->changePassword($password, false);
            if (! $passwordResult->isValid()) {
                $form->sessionMessage(_t('Account.NO_VALID_PASSWORD', 'You need to enter a valid password.') . ' ' . ShopAccountFormPasswordValidator::messages_as_string($passwordResult), 'bad');

                return $this->controller->redirectBack();
            }
        }
        if ($member->validate()->isValid()) {
            try {
                $member->write();
            } catch (ValidationException $exception) {
                $form->sessionMessage(_t('Account.NO_VALID_DATA', 'Your details can not be updated.'), 'bad');

                return $this->controller->redirectBack();
            }
            if ($link) {
                return $this->controller->redirect($link);
            }
            $form->sessionMessage(_t('Account.DETAILSSAVED', 'Your details have been saved.'), 'good');

            return $this->controller->redirectBack();
        }
        $form->sessionMessage(_t('Account.NO_VALID_DATA', 'Your details can not be updated.'), 'bad');

        return $this->controller->redirectBack();
    }
}
