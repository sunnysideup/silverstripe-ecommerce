<?php

/**
 * @Description: ExpiryDate field, contains validation and formspec for expirydate fields.
 * This can be useful when collecting a credit card.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class ExpiryDateField extends TextField
{
    public function __construct($name, $title = null, $value = '', $form = null)
    {
        /*
        $monthValue = '';
        $yearValue = '';
        if(strlen($this->value) == 4) {
            $monthValue = substr($value, 0, 2);
            $yearValue = "20".substr($value, 2, 2);
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

    /**
     *@return HTML
     **/
    public function Field($properties = [])
    {
        $monthValue = '';
        $yearValue = '';
        if (strlen($this->value) === 4) {
            $monthValue = substr($this->value, 0, 2);
            $yearValue = substr($this->value, 2, 2);
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
     *@return string
     **/
    public function dataValue()
    {
        if (is_array($this->value)) {
            $string = '';
            foreach ($this->value as $part) {
                $part = str_pad($part, 2, '0', STR_PAD_LEFT);
                $string .= trim($part);
            }

            return $string;
        }
        return $this->value;
    }

    /**
     * @param Validator $validator
     *
     * @return bool
     **/
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
        $monthValue = substr($this->value, 0, 2);
        $yearValue = '20' . substr($this->value, 2, 2);
        $ts = strtotime(Date('Y-m-01')) - (60 * 60 * 24);
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
     * @return ReadonlyField
     */
    public function performReadonlyTransformation()
    {
        return $this->castedCopy('ReadonlyField')
            ->setTitle($this->title)
            ->setValue(substr($this->value, 0, 2) . '/' . substr($this->value, 2, 2));
    }

    /**
     * @param string $title
     *
     * @return ConfirmedPasswordField
     */
    public function setRightTitle($title)
    {
        /*
        foreach($this->children as $field) {
            $field->setRightTitle($title);
        }
        */
        parent::setRightTitle($title);

        return $this;
    }

    /**
     * @param array $titles 2 entry array with the customized title for each
     *                      of the 2 children.
     *
     * @return ConfirmedPasswordField
     */
    public function setChildrenTitles($titles)
    {
        /*
        if(is_array($titles) && count($titles) == 2) {
            foreach($this->children as $field) {
                if(isset($titles[0])) {
                    $field->setTitle($titles[0]);
                    array_shift($titles);
                }
            }
        }
        return $this;
        */
    }

    /**
     * Value is sometimes an array, and sometimes a single value, so we need
     * to handle both cases.
     *
     * @param mixed $value
     *
     * @return ConfirmedPasswordField
     */
    public function setValue($value, $data = null)
    {

        //store this for later
        $oldValue = $this->value;
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
     **/
    protected function yearArray()
    {
        $list = [];
        $i = 0;
        for ($i = 0; $i < 12; ++$i) {
            $ts = strtotime('+' . $i . ' year');
            $list[Date('y', $ts)] = Date('Y', $ts);
        }

        return $list;
    }

    /**
     * @param $array - list of options...
     * @param string $currentValue
     *
     * @return string (html)
     **/
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
     **/
    protected function monthArray()
    {
        $shortMonths = EcommerceConfig::get('ExpiryDateField', 'short_months');
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
