<?php

namespace Sunnysideup\Ecommerce\Pages;

use convert;









use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\OrderForm;
use Sunnysideup\Ecommerce\Forms\OrderFormAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Process\CheckoutPageStepDescription;

class CheckoutPageController extends CartPageController
{
    /**
     * STEP STUFF ---------------------------------------------------------------------------.
     */

    /**
     *@var string
     **/
    protected $currentStep = '';

    /**
     *@var array
     **/
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
     * FOR STEP STUFF SEE BELOW.
     **/

    /**
     * Standard SS function
     * if set to false, user can edit order, if set to true, user can only review order.
     **/
    public function init()
    {
        parent::init();

        // TODO: find replacement for: Requirements::themedCSS(CheckoutPage::class, 'ecommerce');
        $ajaxifyArray = EcommerceConfig::get('CheckoutPage_Controller', 'ajaxify_steps');
        if (count($ajaxifyArray)) {
            foreach ($ajaxifyArray as $js) {
                Requirements::javascript($js);
            }
        }
        Requirements::javascript('ecommerce/javascript/EcomPayment.js');
        Requirements::customScript(
            '
            if (typeof EcomOrderForm != "undefined") {
                EcomOrderForm.set_TermsAndConditionsMessage(\'' . convert::raw2js($this->TermsAndConditionsMessage) . '\');
            }',
            'TermsAndConditionsMessage'
        );
        $this->steps = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
        $this->currentStep = $this->request->Param('ID');
        if ($this->currentStep && in_array($this->currentStep, $this->steps, true)) {
            //do nothing
        } else {
            $this->currentStep = array_shift($this->steps);
        }
        //redirect to current order -
        // this is only applicable when people submit order (start to pay)
        // and then return back

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: Session:: (case sensitive)
         * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
         * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        if ($checkoutPageCurrentOrderID = SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get('CheckoutPageCurrentOrderID')) {
            if ($this->currentOrder->ID !== $checkoutPageCurrentOrderID) {
                $this->clearRetrievalOrderID();
            }
        }
        if ($this->currentOrder) {
            $this->setRetrievalOrderID($this->currentOrder->ID);
        }
        $this->includeGoogleAnalyticsCode();
    }

    /**
     * Returns a ArrayList of {@link OrderModifierForm} objects. These
     * forms are used in the OrderInformation HTML table for the user to fill
     * in as needed for each modifier applied on the site.
     *
     * @return ArrayList (ModifierForms) | Null
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
        $form = OrderFormAddress::create($this, OrderFormAddress::class);
        $this->data()->extend('updateOrderFormAddress', $form);
        //load session data

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: Session:: (case sensitive)
         * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
         * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        if ($data = SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get("FormInfo.{$form->FormName()}.data")) {
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
        $form = OrderForm::create($this, OrderForm::class);
        $this->data()->extend('updateOrderForm', $form);
        //load session data
        if ($data = Session :: get("FormInfo.{$form->FormName()}.data")) {
            $form->loadDataFrom($data);
        }

        return $form;
    }

    /**
     * Can the user proceed? It must be an editable order (see @link CartPage)
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
     * @param int $number - if set, it returns that one step.
     */
    public function CheckoutSteps($number = 0)
    {
        $steps = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
        if ($number) {
            $code = $steps[$number - 1];

            return CheckoutPageStepDescription::get()->filter(['Code' => $code])->first();
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
                            $do->Link = $this->Link('checkoutstep') . '/' . $do->Code . '/';
                        }
                        $do->LinkingMode = "link ${completedClass}";
                    }
                    $do->Completed = $completed;
                    $returnData->push($do);
                }
            }
        }
        if (EcommerceConfig::get('OrderConfirmationPage_Controller', 'include_as_checkout_step')) {
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
    public function StepsContentAbove($number)
    {
        $do = $this->CheckoutSteps($number);
        if ($do) {
            return $do->Above;
        }

        return '';
    }

    /**
     * returns the bottom of the page content for the Checkout Step.
     *
     * @param int $number
     *
     * @return string
     */
    public function StepsContentBelow($number)
    {
        $do = $this->CheckoutSteps($number);
        if ($do) {
            return $do->Below;
        }

        return '';
    }

    /**
     * sets the current checkout step
     * if it is ajax it returns the current controller
     * as the inner for the page.
     *
     * @param SS_HTTPRequest $request
     *
     * @return array
     */
    public function checkoutstep(HTTPRequest $request)
    {
        if ($this->request->isAjax()) {
            Requirements::clear();

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: ->RenderWith( (ignore case)
             * NEW: ->RenderWith( (COMPLEX)
             * EXP: Check that the template location is still valid!
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
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
     **/
    public function CanShowStep($step)
    {
        if ($this->ShowOnlyCurrentStep()) {
            $outcome = $step === $this->currentStep;
        } else {
            $outcome = in_array($step, $this->steps, true);
        }

        // die($step.'sadf'.$outcome);
        return $outcome;
    }

    /**
     * Is this the final step in the process.
     *
     * @return bool
     */
    public function ShowOnlyCurrentStep()
    {
        return $this->currentStep ? true : false;
    }

    /**
     * Is this the final step in the process?
     *
     * @return bool
     */
    public function IsFinalStep()
    {
        foreach ($this->steps as $finalStep) {
            //do nothing...
        }

        return $this->currentStep === $finalStep;
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

    protected function includeGoogleAnalyticsCode()
    {
        if ($this->EnableGoogleAnalytics && $this->currentOrder && (Director::isLive() || isset($_GET['testanalytics']))) {
            $var = EcommerceConfig::get('OrderConfirmationPage_Controller', 'google_analytics_variable');
            if ($var) {
                $currencyUsedObject = $this->currentOrder->CurrencyUsed();
                if ($currencyUsedObject) {
                    $currencyUsedString = $currencyUsedObject->Code;
                }
                if (empty($currencyUsedString)) {
                    $currencyUsedString = EcommerceCurrency::default_currency_code();
                }
                $js = '
                jQuery("#OrderForm_OrderForm").on(
                    "submit",
                    function(){
                        ' . $var . '(\'require\', \'ecommerce\');
                        ' . $var . '(
                            \'ecommerce:addTransaction\',
                            {
                                \'id\': \'' . $this->currentOrder->ID . '\',
                                \'revenue\': \'' . $this->currentOrder->getSubTotal() . '\',
                                \'currency\': \'' . $currencyUsedString . '\'
                            }
                        );
                        ' . $var . '(\'ecommerce:send\');
                    }
                );
    ';
                Requirements::customScript($js, 'GoogleAnalyticsEcommerce');
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
            $key = array_search($this->currentStep, $this->steps, true);
            ++$key;
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
}
