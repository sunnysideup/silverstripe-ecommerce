<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Forms\OrderForm;
use Sunnysideup\Ecommerce\Forms\OrderFormAddress;
use Sunnysideup\Ecommerce\Model\Process\CheckoutPageStepDescription;

/**
 * Class \Sunnysideup\Ecommerce\Pages\CheckoutPageController
 *
 * @property \Sunnysideup\Ecommerce\Pages\CheckoutPage $dataRecord
 * @method \Sunnysideup\Ecommerce\Pages\CheckoutPage data()
 * @mixin \Sunnysideup\Ecommerce\Pages\CheckoutPage
 * @mixin \Sunnysideup\EcommerceGoogleAnalytics\CheckoutPageExtensionController
 */
class CheckoutPageController extends CartPageController
{
    /**
     * STEP STUFF ---------------------------------------------------------------------------.
     */

    /**
     * @var string
     */
    protected $currentStep = '';

    /**
     * @var array
     */
    protected $steps = [];

    private static $allowed_actions = [
        'checkoutstep',
        'OrderFormAddress',
        'saveorder',
        'CreateAccountForm',
        'retrieveorder',
        'loadorder',
        'startneworder',
        'showorder',
        'LoginForm',
        'OrderForm',
    ];

    /**
     * @var array
     */
    private static $checkout_steps = [
        'orderitems',
        'orderformaddress',
        'orderconfirmationandpayment',
    ];

    /**
     *
     * e.g. ?foo=bar
     * OR #mycart
     * @var string
     */
    private static string $anchor_to_add_to_checkout_steps = '';

    /**
     * @var array
     */
    private static $ajaxify_steps = [];

    /**
     * Returns a ArrayList of {@link OrderModifierForm} objects. These
     * forms are used in the OrderInformation HTML table for the user to fill
     * in as needed for each modifier applied on the site.
     *
     * @return \SilverStripe\ORM\ArrayList (ModifierForms)|null
     */
    public function ModifierForms()
    {
        if ($this->currentOrder) {
            return $this->currentOrder->getModifierForms();
        }

        return null;
    }

    /**
     * Returns a form allowing a user to enter their
     * details to checkout their order.
     *
     * @return OrderForm object
     */
    public function OrderFormAddress()
    {
        $form = OrderFormAddress::create($this, 'OrderFormAddress');
        $this->data()->extend('updateOrderFormAddress', $form);
        $data = $this->getRequest()->getSession()->get("FormInfo.{$form->FormName()}.data");
        //load session data
        if ($data) {
            $form->loadDataFrom($data);
        }

        return $form;
    }

    /**
     * Returns a form allowing a user to enter their
     * details to checkout their order.
     *
     * @return OrderForm object
     */
    public function OrderForm()
    {
        $form = OrderForm::create($this, 'OrderForm');
        $this->data()->extend('updateOrderForm', $form);
        //load session data
        $data = $this->getRequest()->getSession()->get("FormInfo.{$form->FormName()}.data");
        if ($data) {
            $form->loadDataFrom($data);
        }

        return $form;
    }

    /**
     * Can the user proceed? It must be an editable order (see @see CartPage)
     * and is must also contain items.
     *
     * @return bool
     */
    public function CanCheckout()
    {
        return $this->currentOrder->getTotalItems() && ! $this->currentOrder->IsSubmitted();
    }

    /**
     * Catch for incompatable coding only....
     *
     * @param mixed $request
     */
    public function ModifierForm($request)
    {
        user_error('Make sure that you set the controller for your ModifierForm to a controller directly associated with the Modifier', E_USER_WARNING);

        return [];
    }

    /**
     * returns a dataobject set of the steps.
     * Or just one step if that is more relevant.
     *
     * @param int $number - if set, it returns that one step
     */
    public function CheckoutSteps($number = 0)
    {
        $steps = EcommerceConfig::get(CheckoutPageController::class, 'checkout_steps');
        if ($number) {
            $code = $steps[$number - 1];

            return DataObject::get_one(CheckoutPageStepDescription::class, ['Code' => $code]);
        }
        $returnData = ArrayList::create();
        $completed = 1;
        $completedClass = 'completed';
        $seenCodes = [];
        foreach ($steps as $code) {
            if (! in_array($code, $seenCodes, true)) {
                $seenCodes[$code] = $code;
                $do = CheckoutPageStepDescription::get()->filter(['Code' => $code])->first();
                if ($do) {
                    if ($this->currentStep && $do->Code === $this->currentStep) {
                        $do->LinkingMode = 'current';
                        $completed = 0;
                        $completedClass = 'notCompleted';
                    } else {
                        if ($completed) {
                            $do->Link = Controller::join_links($this->Link('checkoutstep'), $do->Code);
                        }
                        $do->LinkingMode = "link {$completedClass}";
                    }
                    $do->Completed = $completed;
                    $returnData->push($do);
                }
            }
        }
        if (EcommerceConfig::get(OrderConfirmationPageController::class, 'include_as_checkout_step')) {
            $orderConfirmationPage = DataObject::get_one(OrderConfirmationPage::class);
            if ($orderConfirmationPage) {
                $do = $orderConfirmationPage->CurrentCheckoutStep(false);
                if ($do) {
                    $returnData->push($do);
                }
            }
        }

        return $returnData;
    }

    /**
     * returns the heading for the Checkout Step.
     *
     * @param int $number
     *
     * @return string
     */
    public function StepsContentHeading($number)
    {
        $do = $this->CheckoutSteps($number);
        if ($do) {
            return $do->Heading;
        }

        return '';
    }

    /**
     * returns the top of the page content for the Checkout Step.
     *
     * @param int $number
     *
     * @return string
     */
    public function StepsContentAbove($number): DBHTMLText
    {
        return $this->aboveBelowContent($number, 'Above');
    }
    /**
     * returns the bottom of the page content for the Checkout Step.
     *
     * @param int $number
     *
     * @return string
     */
    public function StepsContentBelow($number): DBHTMLText
    {
        return $this->aboveBelowContent($number, 'Below');
    }

    protected function aboveBelowContent($number, $fieldName)
    {
        $do = $this->CheckoutSteps($number);
        $v = '';
        if ($do) {
            $v = $do->$fieldName;
        }

        return DBHTMLText::create_field('HTMLText', $v);
    }

    /**
     * sets the current checkout step
     * if it is ajax it returns the current controller
     * as the inner for the page.
     *
     * @return array
     */
    public function checkoutstep(HTTPRequest $request)
    {
        if ($this->request->isAjax()) {
            Requirements::clear();

            return $this->RenderWith('Sunnysideup\Ecommerce\Includes\LayoutCheckoutPageInner');
        }

        return [];
    }

    /**
     * when you extend the CheckoutPage you can change this...
     *
     * @return bool
     */
    public function HasCheckoutSteps()
    {
        return true;
    }

    /**
     * @param string $step
     *
     * @return bool
     */
    public function CanShowStep($step)
    {
        return $this->ShowOnlyCurrentStep() ? $step === $this->currentStep : in_array($step, $this->steps, true);
        // die($step.'sadf'.$outcome);
    }

    /**
     * Is this the final step in the process.
     *
     * @return bool
     */
    public function ShowOnlyCurrentStep()
    {
        return (bool) $this->currentStep;
    }

    /**
     * Is this the final step in the process?
     *
     * @return bool
     */
    public function IsFinalStep()
    {
        return $this->currentStep === $this->steps[array_key_last($this->steps)];
    }

    /**
     * returns the percentage of steps done (0 - 100).
     *
     * @return int
     */
    public function PercentageDone()
    {
        return round($this->currentStepNumber() / $this->numberOfSteps(), 2) * 100;
    }

    /**
     * FOR STEP STUFF SEE BELOW.
     */

    /**
     * Standard SS function
     * if set to false, user can edit order, if set to true, user can only review order.
     */
    protected function init()
    {
        parent::init();


        $this->steps = EcommerceConfig::get(CheckoutPageController::class, 'checkout_steps');
        $this->currentStep = $this->request->Param('ID');
        if (!($this->currentStep && in_array($this->currentStep, $this->steps, true))) {
            $this->currentStep = $this->steps[array_key_first($this->steps)];
        }
        //redirect to current order -
        // this is only applicable when people submit order (start to pay)
        // and then return back
        $checkoutPageCurrentOrderID = $this->getRequest()->getSession()->get('CheckoutPageCurrentOrderID');
        if ($checkoutPageCurrentOrderID) {
            if ($this->currentOrder->ID !== $checkoutPageCurrentOrderID) {
                $this->clearRetrievalOrderID();
            }
        }
        if ($this->currentOrder) {
            $this->setRetrievalOrderID($this->currentOrder->ID);
        }
        if ($this->IsFinalStep()) {
            Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomPayment.js');
            Requirements::customScript(
                'window.LinkToSendReferral = \'' . $this->getLinkToSendReferral() . '\';',
                'LinkToSendReferral'
            );

            Requirements::customScript(
                '
            if (typeof EcomOrderForm !== "undefined") {
                EcomOrderForm.set_TermsAndConditionsMessage(\'' . Convert::raw2js($this->TermsAndConditionsMessage) . '\');
            }',
                'TermsAndConditionsMessage'
            );
        }
        Requirements::themedCSS('client/css/CheckoutPage');
        $ajaxifyArray = EcommerceConfig::get(CheckoutPageController::class, 'ajaxify_steps');
        if (is_array($ajaxifyArray) && count($ajaxifyArray)) {
            foreach ($ajaxifyArray as $js) {
                Requirements::javascript($js);
            }
        }
    }

    /**
     * returns the number of the current step (e.g. step 1).
     *
     * @return int
     */
    protected function currentStepNumber()
    {
        $key = 1;
        if ($this->currentStep) {
            $key = (int) array_search($this->currentStep, $this->steps, true);
            $key++;
        }

        return $key;
    }

    /**
     * returns the total number of steps (e.g. 3)
     * we add one for the confirmation page.
     *
     * @return int
     */
    protected function numberOfSteps()
    {
        return count($this->steps) + 1;
    }

    /**
     * Here are some additional rules that can be applied to steps.
     * If you extend the checkout page, you canm overrule these rules.
     */
    protected function applyStepRules()
    {
        //no items, back to beginning.
        //has step xxx been completed? if not go back one?
        //extend
        //reset current step if different
    }

    protected function getLinkToSendReferral()
    {
        return Director::absoluteURL(ShoppingCartController::add_referral_link());
    }
}
