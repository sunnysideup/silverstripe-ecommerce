<?php


/**
 * @Description: allows customer to cancel their order.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms

 **/
class OrderForm_Cancel extends Form
{
    public function __construct(Controller $controller, $name, Order $order)
    {
        $fields = new FieldList(
            [
                new HeaderField('CancelOrderHeading', _t('OrderForm.CANCELORDER', 'Changed your mind?'), 3),
                new TextField('CancellationReason', _t('OrderForm.CANCELLATIONREASON', 'Reason for cancellation')),
                new HiddenField('OrderID', '', $order->ID),
            ]
        );
        $actions = new FieldList(
            new FormAction('docancel', _t('OrderForm.CANCELORDER', 'Cancel this order'))
        );
        $requiredFields = [];
        $validator = OrderForm_Cancel_Validator::create($requiredFields);
        parent::__construct($controller, $name, $fields, $actions, $validator);
        //extension point
        $this->extend('updateFields', $fields);
        $this->setFields($fields);
        $this->extend('updateActions', $actions);
        $this->setActions($actions);
        $this->extend('updateValidator', $validator);
        $this->setValidator($validator);

        $oldData = Session::get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderForm_Cancel', $this);
    }

    /**
     * Form action handler for OrderForm_Cancel.
     *
     * Take the order that this was to be change on,
     * and set the status that was requested from
     * the form request data.
     *
     * @param array $data The form request data submitted
     * @param Form  $form The {@link Form} this was submitted on
     */
    public function docancel(array $data, Form $form, SS_HTTPRequest $request)
    {
        $SQLData = Convert::raw2sql($data);
        $member = Member::currentUser();
        if ($member) {
            if (isset($SQLData['OrderID'])) {
                $order = Order::get()->byID(intval($SQLData['OrderID']));
                if ($order) {
                    if ($order->canCancel()) {
                        $reason = '';
                        if (isset($SQLData['CancellationReason'])) {
                            $reason = $SQLData['CancellationReason'];
                        }
                        $order->Cancel($member, $reason);
                        $form->sessionMessage(
                            _t(
                                'OrderForm.CANCELLED',
                                'Order has been cancelled.'
                            ),
                            'good'
                        );

                        return $this->controller->redirectBack();
                    }
                }
            }
        }
        $form->sessionMessage(
            _t(
                'OrderForm.COULDNOTCANCELORDER',
                'Sorry, order could not be cancelled.'
            ),
            'bad'
        );
        $this->controller->redirectBack();

        return false;
    }

    /**
     * saves the form into session.
     *
     * @param array $data - data from form.
     */
    public function saveDataToSession()
    {
        $data = $this->getData();
        Session::set("FormInfo.{$this->FormName()}.data", $data);
    }
}
