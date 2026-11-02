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
    /**
     * The maximum number of characters for the note.
     *
     * @var int
     */
    private static $max_note_length = 2000;

    /**
     * The maximum number of feedback entries for one order.
     *
     * @var int
     */
    private static $max_feedback_entries_per_order = 3;

    protected $order;

    protected $_orderConfirmationPage;

    public function __construct(Controller $controller, $name, Order $order)
    {
        $this->order = $order;
        $values = $this->getValueFromOrderConfirmationPage('FeedbackValuesOptions');
        $values = explode(',', (string) $values);

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
            'Rating',
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

    public function dofeedback(array $data, Form $form, HTTPRequest $request)
    {
        $order = $this->order;
        // feedback can only be left for an order that has been submitted
        // and only a limited number of times (anyone with the link can use this form).
        if ($order && $order->exists() && $order->IsSubmitted()) {
            $rating = $data['Rating'] ?? '';
            $note = $data['Note'] ?? '';
            $allowedRatings = [];
            $ratingField = $this->Fields()->dataFieldByName('Rating');
            if ($ratingField instanceof OptionsetField) {
                $allowedRatings = array_map('strval', array_keys($ratingField->getSource()));
            }
            $maxPerOrder = (int) Config::inst()->get(self::class, 'max_feedback_entries_per_order');
            if (! is_string($rating) || ! in_array($rating, $allowedRatings, true) || ! is_string($note)) {
                $form->sessionMessage(
                    _t('OrderFormFeedback.INVALID_FEEDBACK', 'Please select one of the options.'),
                    'bad'
                );
            } elseif ($maxPerOrder > 0 && OrderFeedback::get()->filter(['OrderID' => $order->ID])->count() >= $maxPerOrder) {
                $form->sessionMessage(
                    _t('OrderFormFeedback.TOO_MUCH_FEEDBACK', 'Thank you, we have already received your feedback for this order.'),
                    'good'
                );
            } else {
                $object = OrderFeedback::create();
                $object->Note = mb_substr(trim(strip_tags($note)), 0, (int) Config::inst()->get(self::class, 'max_note_length')) ?: 'no note';
                $object->Rating = $rating;
                $object->OrderID = $order->ID;
                $object->write();
                $form->sessionMessage(
                    $this->getValueFromOrderConfirmationPage('FeedbackFormThankYou'),
                    'good'
                );
            }
        } else {
            $form->sessionMessage(
                _t(
                    'OrderFormFeedback.COULD_NOT_RECORD_FEEDBACK',
                    'Sorry, order feedback could not be recorded.'
                ),
                'bad'
            );
        }

        if ($order) {
            return $this->controller->redirect($order->FeedbackLink());
        }

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

    protected function getValueFromOrderConfirmationPage($value)
    {
        $page = $this->getOrderConfirmationPage();
        if ($page) {
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
