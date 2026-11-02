<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Forms\Validation\OrderFormPaymentValidator;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;

class OrderFormPayment extends Form
{
    /**
     * The order this form was built for. dopayment() must only ever act on this order,
     * never on an order ID taken from the submitted data.
     */
    protected ?Order $order = null;

    /**
     * @param string $name
     * @param string $returnToLink
     */
    public function __construct(Controller $controller, $name, Order $order, $returnToLink = '')
    {
        $this->order = $order;
        $requiredFields = [];
        $fields = FieldList::create(HiddenField::create('OrderID', '', $order->ID));
        if ($returnToLink) {
            $fields->push(HiddenField::create('returntolink', '', Convert::raw2att($returnToLink)));
        }

        if ($order->PaymentIsPending()) {
            $fields->push(
                LiteralField::create('PaymentIsPending', '<p class="message warning">' . _t(
                    'OrderForm.PAYMENTISPENDING',
                    "Your payment is awaiting confirmation. If you haven't completed a payment yet, you may proceed with an alternative payment now."
                ) . '</p>')
            );
        }

        $bottomFields = CompositeField::create();
        $bottomFields->addExtraClass('bottomOrder');
        if ($order->Total() > 0) {
            $paymentFields = EcommercePayment::combined_form_fields($order->getTotalAsMoney()->NiceLongSymbol(false), $order);
            foreach ($paymentFields as $paymentField) {
                $bottomFields->push($paymentField);
            }

            $paymentRequiredFields = EcommercePayment::combined_form_requirements($order);
            if ($paymentRequiredFields !== []) {
                $requiredFields = array_merge($requiredFields, $paymentRequiredFields);
            }
        } else {
            $bottomFields->push(HiddenField::create('PaymentMethod', '', ''));
        }

        $fields->push($bottomFields);
        $actions = FieldList::create(FormAction::create('dopayment', _t('OrderForm.PAYORDER', 'Pay balance')));

        $validator = OrderFormPaymentValidator::create($requiredFields);
        parent::__construct($controller, $name, $fields, $actions, $validator);

        //extension point
        $this->extend('updateFields', $fields);
        $this->setFields($fields);
        $this->extend('updateActions', $actions);
        $this->setActions($actions);
        $this->extend('updateValidator', $validator);
        $this->setValidator($validator);

        $this->setFormAction($controller->Link($name));
        $oldData = Controller::curr()->getRequest()->getSession()->get(sprintf('FormInfo.%s.data', $this->FormName()));
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }

        $this->extend('updateOrderFormPayment', $this);
    }

    /**
     * @param array $data
     * @param Form  $form
     *
     * @return mixed
     */
    public function dopayment($data, $form)
    {
        $order = $this->order;
        $orderID = (int) ($data['OrderID'] ?? 0);
        // the submitted OrderID must match the order this form was built for
        // and the current user must be allowed to view it.
        if ($order && $order->exists() && $orderID === (int) $order->ID && $order->canView()) {
            if ($order->canPay()) {
                $formHelper = EcommercePayment::ecommerce_payment_form_setup_and_validation_object();
                if ($formHelper->validatePayment($order, $data, $form)) {
                    $outcome = $formHelper->processPaymentFormAndReturnNextStep($order, $data, $form);
                    if ($outcome === null) {
                        return $this->controller->redirectBack();
                    }
                    return $outcome;
                }
                //error messages are set in validation
                return $this->controller->redirectBack();
            }
            $form->sessionMessage(_t('OrderForm.NO_PAYMENTS_CAN_BE_MADE_FOR_THIS_ORDER', 'No payments can be made for this order.'), 'bad');

            return $this->controller->redirectBack();
        }

        $form->sessionMessage(_t('OrderForm.COULDNOTPROCESSPAYMENT', 'Sorry, we could not find the Order for payment.'), 'bad');

        return $this->controller->redirectBack();
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
}
