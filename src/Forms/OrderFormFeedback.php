<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Forms\Validation\OrderFormFeedbackValidator;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderFeedback;

use Sunnysideup\Ecommerce\Pages\OrderConfirmationPage;

class OrderFormFeedback extends Form
{
    protected $order;

    protected $_orderConfirmationPage;

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
        $validator = OrderFormFeedbackValidator::create($requiredFields);
        parent::__construct($controller, $name, $fields, $actions, $validator);
        $oldData = Controller::curr()->getRequest()->getSession()->get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderFormFeedback', $this);
    }

    /**
     * @param array        $data The form request data submitted
     * @param Form         $form The {@link Form} this was submitted on
     * @return \SilverStripe\Control\HTTPRequest The {@link Form} this was submitted on
     */
    public function dofeedback(array $data, Form $form, HTTPRequest $request)
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
                'OrderFormFeedback.COULD_NOT_RECORD_FEEDBACK',
                'Sorry, order feedback could not be recorded.'
            ),
            'bad'
        );

        return $this->controller->redirect($this->order->FeedbackLink());
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

    protected function getValueFromOrderConfirmationPage($value)
    {
        if ($page = $this->getOrderConfirmationPage()) {
            return $page->{$value};
        }
        $defaults = Config::inst()->get(OrderConfirmationPage::class, 'defaults');
        if ($defaults && is_array($defaults) && isset($defaults[$value])) {
            return $defaults[$value];
        }
        return _t('OrderFormFeedback.' . $value, 'OrderFormFeedback.' . $value . ' value not set in translations');
    }

    protected function getOrderConfirmationPage()
    {
        if (! $this->_orderConfirmationPage) {
            $this->_orderConfirmationPage = DataObject::get_one(OrderConfirmationPage::class);
        }
        return $this->_orderConfirmationPage;
    }
}
