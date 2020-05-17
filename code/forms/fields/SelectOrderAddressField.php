<?php
/**
 * A field that allows the user to select an old address for the current order.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class SelectOrderAddressField extends OptionsetField
{
    /**
     * @var DataList
     */
    protected $addresses = null;

    /**
     * Creates a new optionset field.
     *
     * @param string    $name      The field name
     * @param string    $title     The field title
     * @param ArrayList $addresses
     * @param string    $value     The current value
     * @param Form      $form      - The parent form
     */
    public function __construct($name, $title = '', $addresses = null, $value = '', Form $form = null)
    {
        $this->addresses = $addresses;
        $source = [];
        if ($this->addresses && $this->addresses instanceof SS_List) {
            $source = [];
            foreach ($this->addresses as $address) {
                $source[$address->ID] = $address->FullString();
            }
        }
        parent::__construct($name, $title, $source, $value, $form);
    }

    /**
     * Standard SS method - returns the string for the Field.
     * Note that we include JS from this method.
     *
     * @return HTML
     */
    public function Field($properties = [])
    {
        $jsArray = [];
        $js = '
            if(typeof EcomSelectOrderAddressFieldOptions === "undefined") {
                EcomSelectOrderAddressFieldOptions = [];
            }
        ';
        $jsonCompare = [];
        if ($this->addresses) {
            foreach ($this->addresses as $address) {
                $js .= '
            EcomSelectOrderAddressFieldOptions.push({id: ' . $address->ID . ', address: ' . $address->JSONData() . '});
                ';
            }
        }
        Requirements::javascript('ecommerce/javascript/EcomSelectOrderAddressField.js');
        Requirements::customScript($js, 'Update_' . $this->getName());

        return parent::Field();
    }
}
