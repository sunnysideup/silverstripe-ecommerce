<?php


class OrderForm_Feedback extends Form
{
    protected $order = null;

    protected $_orderConfirmationPage = null;

    public function __construct(Controller $controller, $name, Order $order)
    {
        $this->order = $order;
        $values = $this->getValueFromOrderConfirmationPage('FeedbackValuesOptions');
        $values = explode(',', $values);
        $newValues = [];
        foreach ($values as $value) {
            $value = trim($value);
            $newValues[Convert::raw2att($value)] = $value;
        }
        $fields = FieldList::create(
            [
                OptionsetField::create(
                    'Rating',
                    $this->getValueFromOrderConfirmationPage('FeedbackValuesFieldLabel'),
                    $newValues
                ),
                TextareaField::create('Note', $this->getValueFromOrderConfirmationPage('FeedbackNotesFieldLabel')),
            ]
        );
        $actions = FieldList::create(
            FormAction::create('dofeedback', $this->getValueFromOrderConfirmationPage('FeedbackFormSubmitLabel'))
        );
        $requiredFields = [
            'FeedbackValue',
        ];
        $validator = OrderForm_Feedback_Validator::create($requiredFields);
        parent::__construct($controller, $name, $fields, $actions, $validator);

        $oldData = Session::get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderForm_Feedback', $this);
    }

    /**
     * @param array        $data The form request data submitted
     * @param Form         $form The {@link Form} this was submitted on
     * @param HTTPRequest  $request The {@link Form} this was submitted on
     */
    public function dofeedback(array $data, Form $form, SS_HTTPRequest $request)
    {
        if ($this->order) {
            $object = OrderFeedback::create();
            $object->Note = Convert::raw2sql($data['Note']);
            $object->Rating = Convert::raw2sql($data['Rating']);
            $object->OrderID = $this->order->ID;
            $object->write();
            $form->sessionMessage(
                $this->getValueFromOrderConfirmationPage('FeedbackFormThankYou'),
                'good'
            );

            return $this->controller->redirect($this->order->FeedbackLink());
        }
        $form->sessionMessage(
            _t(
                'OrderForm_Feedback.COULD_NOT_RECORD_FEEDBACK',
                'Sorry, order feedback could not be recorded.'
            ),
            'bad'
        );

        return $this->controller->redirect($this->order->FeedbackLink());
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
        if ($page = $this->getOrderConfirmationPage()) {
            return $page->{$value};
        }
        $defaults = Config::inst()->get('OrderConfirmationPage', 'defaults');
        if ($defaults && is_array($defaults) && isset($defaults[$value])) {
            return $defaults[$value];
        }
        return _t('OrderForm_Feedback.' . $value, 'OrderForm_Feedback.' . $value . ' value not set in translations');
    }

    protected function getOrderConfirmationPage()
    {
        if (! $this->_orderConfirmationPage) {
            $this->_orderConfirmationPage = DataObject::get_one('OrderConfirmationPage');
        }
        return $this->_orderConfirmationPage;
    }
}
