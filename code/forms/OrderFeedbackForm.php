<?php


class OrderFeedbackForm extends Form
{


    public function __construct(Controller $controller, $name, Order $order)
    {
        $values = $this->getValueFromOrderConfirmationPage('FeedbackValuesOptions');
        $values = explode(',', $values);
        $newValues = array();
        foreach($values as $value) {
            $value = trim($value);
            $newValues[Convert::raw2attr($value)] = $value;
        }
        $fields = FieldList::create(
            array(
                HeaderField::create('FeedbackHeading', $this->getValueFromOrderConfirmationPage('FeedbackHeader'), 3),
                OptionsetField::create(
                    'Rating',
                    $this->getValueFromOrderConfirmationPage('FeedbackValuesFieldLabel'),
                    $newValues
                ),
                TextareaField::create('Note', $this->getValueFromOrderConfirmationPage('FeedbackNotesFieldLabel'),
                HiddenField::create('OrderID', '', $order->ID),
            )
        );
        $actions = FieldList::create(
            FormAction::create('dofeedback', $this->getValueFromOrderConfirmationPage('FeedbackFormSubmitLabel'))
        );
        $requiredFields = array(
            'FeedbackValue'
        );
        $validator = OrderFeedbackForm_Validator::create($requiredFields);
        parent::__construct($controller, $name, $fields, $actions, $validator);

        $oldData = Session::get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderFeedbackForm', $this);
    }

    /**
     * @param array        $data The form request data submitted
     * @param Form         $form The {@link Form} this was submitted on
     * @param HTTPRequest  $request The {@link Form} this was submitted on
     */
    public function dofeedback(array $data, Form $form, SS_HTTPRequest $request)
    {
        $SQLData = Convert::raw2sql($data);
        if (isset($SQLData['OrderID'])) {
            $order = Order::get()->byID(intval($SQLData['OrderID']));
            if ($order) {
                if ($order->canView()) {
                    $object = OrderFeedback::create($SQLData);
                    $object->OrderID = $order->ID;
                    $objet->write();
                    $form->sessionMessage(
                        $this->getValueFromOrderConfirmationPage('FeedbackFormThankYou'),
                        'good'
                    );

                    return $this->controller->redirectBack();
                }
            }
        }
        $form->sessionMessage(
            _t(
                'OrderFeedbackForm.COULD_NOT_RECORD_FEEDBACK',
                'Sorry, order feedback could not be recorded.'
            ),
            'bad'
        );

        return $this->controller->redirectBack();

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

    protected function getValueFromOrderConfirmationPage($value)
    {
        if($page = $this->getOrderConfirmationPage()) {
            return $page->$value;
        } else {
            $defaults = Config::inst()->get('OrderConfirmationPage', 'defaults');
            if($defaults && is_array($defaults) && isset($defaults[$value])) {
                return $defaults[$value];
            }
            return _t('OrderFeedbackForm.'.$value, 'OrderFeedbackForm.'.$value.' value not set in translations');
        }
    }

    protected $_orderConfirmationPage = null;

    protected function orderConfirmationPage()
    {
        if(! $this->_orderConfirmationPage) {
            $this->_orderConfirmationPage = OrderConfirmationPage::get()->first();
        }
    }

}
