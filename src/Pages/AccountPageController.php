<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Forms\ShopAccountForm;

/**
 * Class \Sunnysideup\Ecommerce\Pages\AccountPageController
 *
 * @property \Sunnysideup\Ecommerce\Pages\AccountPage $dataRecord
 * @method \Sunnysideup\Ecommerce\Pages\AccountPage data()
 * @mixin \Sunnysideup\Ecommerce\Pages\AccountPage
 */
class AccountPageController extends PageController
{
    //TODO: why do we need this?
    private static $allowed_actions = [
        'MemberForm',
    ];

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
        return Security::getCurrentUser();
    }

    /**
     * standard controller function.
     */
    protected function init()
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
        Requirements::themedCSS('client/css/AccountPage');
    }
}
