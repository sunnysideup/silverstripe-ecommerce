<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Control\Email\Email;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Pages\AccountPage;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;
use Sunnysideup\Ecommerce\Pages\OrderConfirmationPage;

/**
 * create default records for e-commerce
 * This does not include any products.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskDefaultRecords extends BuildTask
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    protected $title = 'Create e-commerce default records';

    /**
     * standard SS variable.
     *
     * @var string
     */
    protected $description = 'These default records are basic stuff like an account page, a few products, a product group.';

    public function run($request)
    {
        $update = [];
        $orderStep = singleton(OrderStep::class);
        $orderStep->requireDefaultRecords();
        // ACCOUNT PAGE
        $accountPage = DataObject::get_one(AccountPage::class, null, $cacheDataObjectGetOne = false);
        if (! $accountPage) {
            $accountPage = new AccountPage();
            $accountPage->Title = 'Account';
            $accountPage->MenuTitle = 'Account';
            $accountPage->MetaTitle = 'Account';
            $accountPage->Content = '<p>This is the account page. It is used for shop users to login and change their member details if they have an account.</p>';
            $accountPage->URLSegment = 'account';
            $accountPage->ShowInMenus = false;
            $accountPage->writeToStage(Versioned::DRAFT);
            $accountPage->publishRecursive();
            DB::alteration_message("Account page 'Account' created", 'created');
        } else {
            DB::alteration_message('No need to create an account page, it already exists.');
        }

        //CHECKOUT PAGE

        //CHECKOUT PAGE

        $checkoutPage = DataObject::get_one(CheckoutPage::class, null, $cacheDataObjectGetOne = false);
        if (! $checkoutPage) {
            $checkoutPage = new CheckoutPage();
            $checkoutPage->Content = '<p>This is the checkout page. You can edit all the messages in the Content Management System.</p>';
            $checkoutPage->Title = 'Checkout';
            $checkoutPage->TermsAndConditionsMessage = 'You must agree with the terms and conditions to proceed. ';
            $checkoutPage->MetaTitle = 'Checkout';
            $checkoutPage->MenuTitle = 'Checkout';
            $checkoutPage->URLSegment = 'checkout';
            $update[] = "Checkout page 'Checkout' created";
            $checkoutPage->ShowInMenus = false;

            DB::alteration_message('new checkout page created.', 'created');
        } else {
            DB::alteration_message('No need to create an checkout page, it already exists.');
        }

        if ($checkoutPage) {
            $cacheDataObjectGetOne = false;
            $termsPage = DataObject::get_one(
                \Page::class,
                ['URLSegment' => 'terms-and-conditions'],
                $cacheDataObjectGetOne
            );
            if (0 === $checkoutPage->TermsPageID && $termsPage) {
                $checkoutPage->TermsPageID = $termsPage->ID;
                DB::alteration_message('terms and conditions page linked.', 'created');
            } else {
                DB::alteration_message('No terms and conditions page linked.');
            }

            $checkoutPage->writeToStage(Versioned::DRAFT);
            $checkoutPage->publishRecursive();
            DB::alteration_message('Checkout page saved');

            $orderConfirmationPage = DataObject::get_one(OrderConfirmationPage::class, null, $cacheDataObjectGetOne = false);
            if ($orderConfirmationPage) {
                DB::alteration_message('No need to create an Order Confirmation Page. It already exists.');
            } else {
                $orderConfirmationPage = new OrderConfirmationPage();
                $orderConfirmationPage->ParentID = $checkoutPage->ID;
                $orderConfirmationPage->Title = 'Order confirmation';
                $orderConfirmationPage->MenuTitle = 'Order confirmation';
                $orderConfirmationPage->MetaTitle = 'Order confirmation';
                $orderConfirmationPage->Content = '<p>This is the order confirmation page. It is used to confirm orders after they have been placed in the checkout page.</p>';
                $orderConfirmationPage->URLSegment = 'order-confirmation';
                $orderConfirmationPage->ShowInMenus = false;
                $orderConfirmationPage->writeToStage(Versioned::DRAFT);
                $orderConfirmationPage->publishRecursive();
                DB::alteration_message('Order Confirmation created', 'created');
            }
        }

        $update = [];
        $ecommerceConfig = EcommerceConfig::inst();
        if ($ecommerceConfig) {
            if (! $ecommerceConfig->ReceiptEmail) {
                $ecommerceConfig->ReceiptEmail = Email::config()->admin_email;
                if (! $ecommerceConfig->ReceiptEmail) {
                    user_error("you must set an Admin Email ... Config::modify()->set('Email', 'admin_email', 'foo@bar.nz') ... ", E_USER_NOTICE);
                }

                $update[] = 'created default entry for ReceiptEmail';
            }

            if (! $ecommerceConfig->NumberOfProductsPerPage) {
                $ecommerceConfig->NumberOfProductsPerPage = 12;
                $update[] = 'created default entry for NumberOfProductsPerPage';
            }

            if (count($update)) {
                $ecommerceConfig->write();
                DB::alteration_message($ecommerceConfig->ClassName . ' created/updated: ' . implode(' --- ', $update), 'created');
            }
        }
    }
}
