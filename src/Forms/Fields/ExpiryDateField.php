<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validator;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;

/**
 * @Description: ExpiryDate field, contains validation and formspec for expirydate fields.
 * This can be useful when collecting a credit card.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class ExpiryDateField extends TextField
{
    public function __construct($name, $title = null, $value = '', $form = null)
    {
        /*
        $monthValue = '';
        $yearValue = '';
        if(strlen( (string) $this->value) == 4) {
            $monthValue = substr((string) $value, 0, 2);
            $yearValue = "20".substr((string) $value, 2, 2);
        }
        $this->children = new FieldList(
            $monthField = new DropdownField(
                "{$name}[month]",
                "",
                $this->makeSelectList($this->monthArray(), $monthValue)
            ),
            $yearField = new DropdownField(
                "{$name}[year]",
                "",
                $this->makeSelectList($this->yearArray(), $yearValue)
            )
        );
        $monthField->addExtraClass("");
        $yearField->addExtraClass("");
        // disable auto complete
        foreach($this->children as $child) {
            $child->setAttribute('autocomplete', 'off');
        }
        */
        parent::__construct($name, $title, null, $form);
        $this->setValue($value);
    }

    public function Field($properties = [])
    {
        $monthValue = '';
        $yearValue = '';
        if (4 === strlen((string) $this->value)) {
            $monthValue = substr((string) $this->value, 0, 2);
            $yearValue = substr((string) $this->value, 2, 2);
        }

        return '
            <span id="' . $this->getName() . '_Holder" class="expiryDateField">
                <select class="expiryDate expiryDateMonth" name="' . $this->getName() . '[month]" autocomplete="off" >
                    <option value="" selected="selected">Month</option>' . $this->makeSelectList($this->monthArray(), $monthValue) . '
                </select>
                <select class="expiryDate expiryDateYear" name="' . $this->getName() . '[year]" autocomplete="off" >
                    <option value="" selected="selected">Year</option>' . $this->makeSelectList($this->yearArray(), $yearValue) . '
                </select>
            </span>';
    }

    /**
     * @return string
     */
    public function dataValue()
    {
        if (is_array($this->value)) {
            $string = '';
            foreach ($this->value as $part) {
                $part = str_pad($part, 2, '0', STR_PAD_LEFT);
                $string .= trim((string) $part);
            }

            return $string;
        }

        return $this->value;
    }

    /**
     * @param Validator $validator
     *
     * @return bool
     */
    public function validate($validator)
    {
        // If the field is empty then don't return an invalidation message'
        if (! isset($this->value['month'])) {
            $validator->validationError(
                $this->getName(),
                _t('ExpiryDateField.NO_MONTH', "Please ensure you have entered the expiry date 'month' portion."),
                'bad'
            );

            return false;
        }
        if (! isset($this->value['year'])) {
            $validator->validationError(
                $this->getName(),
                _t('ExpiryDateField.NO_YEAR', "Please ensure you have entered the expiry date 'year' portion."),
                'bad'
            );

            return false;
        }
        $value = str_pad($this->value['month'], 2, '0', STR_PAD_LEFT);
        $value .= str_pad($this->value['year'], 2, '0', STR_PAD_LEFT);
        $this->value = $value;
        // months are entered as a simple number (e.g. 1,2,3, we add a leading zero if needed)
        $monthValue = substr((string) $this->value, 0, 2);
        $yearValue = '20' . substr((string) $this->value, 2, 2);
        $ts = strtotime(date('Y-m-01')) - (60 * 60 * 24);
        $expiryTs = strtotime('20' . $yearValue . '-' . $monthValue . '-01');
        if ($ts > $expiryTs) {
            $validator->validationError(
                $this->getName(),
                _t('ExpiryDateField.PAST_DATE', 'Please ensure you have entered the expiry date correctly.'),
                'bad'
            );

            return false;
        }

        return true;
    }

    /**
     * Makes a read only field with some stars in it to replace the password.
     *
     * @return FormField|ReadonlyField
     */
    public function performReadonlyTransformation()
    {
        return $this->castedCopy(ReadonlyField::class)
            ->setTitle($this->title)
            ->setValue(substr((string) $this->value, 0, 2) . '/' . substr((string) $this->value, 2, 2))
        ;
    }

    /**
     * @param string $title
     */
    public function setDescription($title): self
    {
        /*
        foreach($this->children as $field) {
            $field->setDescription($title);
        }
        */
        parent::setDescription($title);

        return $this;
    }

    /**
     * Value is sometimes an array, and sometimes a single value, so we need
     * to handle both cases.
     *
     * @param mixed      $value
     * @param null|mixed $data
     */
    public function setValue($value, $data = null): self
    {
        //store this for later
        // $oldValue = $this->value;
        $this->value = $value;

        //looking up field by name is expensive, so lets check it needs to change
        /*
        if ($oldValue != $this->value) {
            $this->children->fieldByName($this->getName() . '[month]')->setValue($this->value);
            $this->children->fieldByName($this->getName() . '[year]')->setValue($this->value);
        }
        */
        return $this;
    }

    /**
     * @return array(2000 => 2000, 2001 => 2001, etc...)
     */
    protected function yearArray()
    {
        $list = [];
        $i = 0;
        for ($i = 0; $i < 12; ++$i) {
            $ts = strtotime('+' . $i . ' year');
            $list[date('y', $ts)] = date('Y', $ts);
        }

        return $list;
    }

    /**
     * @param list   $array        of options...
     * @param string $currentValue
     *
     * @return string (html)
     */
    protected function makeSelectList(array $array, $currentValue)
    {
        $string = '';
        foreach ($array as $key => $value) {
            $select = '';
            if ($key === $currentValue) {
                $select = ' selected="selected"';
            }
            $string .= '<option value="' . $key . '"' . $select . '>' . $value . '</option>';
        }

        return $string;
    }

    /**
     * @return array(1 => "Jan", etc...)
     */
    protected function monthArray()
    {
        $shortMonths = EcommerceConfig::get(ExpiryDateField::class, 'short_months');
        if ($shortMonths) {
            return [
                '01' => '01 - Jan',
                '02' => '02 - Feb',
                '03' => '03 - Mar',
                '04' => '04 - Apr',
                '05' => '05 - May',
                '06' => '06 - Jun',
                '07' => '07 - Jul',
                '08' => '08 - Aug',
                '09' => '09 - Sep',
                '10' => '10 - Oct',
                '11' => '11 - Nov',
                '12' => '12 - Dec',
            ];
        }

        return [
            '01' => '01 - January',
            '02' => '02 - February',
            '03' => '03 - March',
            '04' => '04 - April',
            '05' => '05 - May',
            '06' => '06 - June',
            '07' => '07 - July',
            '08' => '08 - August',
            '09' => '09 - September',
            '10' => '10 - October',
            '11' => '11 - November',
            '12' => '12 - December',
        ];
    }
}
