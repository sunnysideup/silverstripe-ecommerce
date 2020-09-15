<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Forms\ShopAccountForm;

class AccountPageController extends PageController
{
    //TODO: why do we need this?
    private static $allowed_actions = [
        'MemberForm',
    ];

    /**
     * standard controller function.
     **/
    public function init()
    {
        parent::init();
        if (! $this->AccountMember() && 1 === 2) {
            $messages = [
                'default' => '<p class="message good">' . _t('Account.LOGINFIRST', 'You will need to log in before you can access the account page. ') . '</p>',
                'logInAgain' => _t('Account.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.'),
            ];
            Security::permissionFailure($this, $messages);

            return false;
        }
        Requirements::themedCSS('AccountPage');
    }

    /**
     * Return a form allowing the user to edit
     * their details with the shop.
     *
     * @return ShopAccountForm
     */
    public function MemberForm()
    {
        return ShopAccountForm::create($this, 'MemberForm', $mustCreateAccount = true);
    }

    /**
     * Returns the current member.
     */
    public function AccountMember()
    {
        return Security::currentUser();
    }

    /**
     * The link that Google et al. need to index.
     * @return string
     */
    public function CanonicalLink()
    {
        $link = $this->Link();
        $this->extend('UpdateCanonicalLink', $link);

        return $link;
    }
}
