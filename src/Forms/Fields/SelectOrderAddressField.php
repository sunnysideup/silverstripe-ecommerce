<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\Requirements;

/**
 * A field that allows the user to select an old address for the current order.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class SelectOrderAddressField extends OptionsetField
{
    /**
     * @var \SilverStripe\ORM\DataList
     */
    protected $addresses;

    /**
     * Creates a new optionset field.
     *
     * @param string                      $name      The field name
     * @param string                      $title     The field title
     * @param \SilverStripe\ORM\ArrayList $addresses
     * @param string                      $value     The current value
     */
    public function __construct($name, $title = '', $addresses = null, $value = '')
    {
        $this->addresses = $addresses;
        $source = [];
        if ($this->addresses && $this->addresses instanceof SS_List) {
            $source = [];
            foreach ($this->addresses as $address) {
                $source[$address->ID] = $address->FullString();
            }
        }
        parent::__construct($name, $title, $source, $value);
    }

    /**
     * Standard SS method - returns the string for the Field.
     * Note that we include JS from this method.
     *
     * @param mixed $properties
     *
     * @return DBHTMLText
     */
    public function Field($properties = [])
    {
        // $jsArray = [];
        $js = '
            if(typeof EcomSelectOrderAddressFieldOptions === "undefined") {
                EcomSelectOrderAddressFieldOptions = [];
            }
        ';
        if ($this->addresses) {
            foreach ($this->addresses as $address) {
                $js .= '
            EcomSelectOrderAddressFieldOptions.push({id: ' . $address->ID . ', address: ' . $address->JSONData() . '});
                ';
            }
        }
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomSelectOrderAddressField.js');
        Requirements::customScript(
            $js,
            'Update_' . $this->getName()
        );

        return parent::Field();
    }
}
