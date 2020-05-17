<?php


/**
 * @Description: A links-based field for increasing, decreasing and setting a order item quantity
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcomQuantityField extends NumericField
{
    /**
     *@var order OrderItem DataObject
     **/
    protected $orderItem = null;

    /**
     *@var Array();???
     **/
    protected $parameters = [];

    /**
     *@var Array()
     **/
    protected $classes = ['ajaxQuantityField'];

    /**
     * max length in digits.
     *
     *@var int
     **/
    protected $maxLength = 3;

    /**
     * max length in digits.
     *
     *@var int
     **/
    protected $fieldSize = 3;

    /**
     *@var string
     **/
    protected $template = 'EcomQuantityField';

    /**
     * the tabindex for the form field
     * we use this so that you can tab through all the
     * quantity fields without disruption.
     * It is saved like this: "FieldName (String)" => tabposition (int).
     *
     * @var array
     **/
    private static $tabindex = [];

    /**
     * @param buyable      $parameters - the buyable / OrderItem
     * @param array | null $parameters - parameters
     **/
    public function __construct($object, $parameters = [])
    {
        Requirements::javascript('ecommerce/javascript/EcomQuantityField.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
        if ($object instanceof BuyableModel) {
            $this->orderItem = ShoppingCart::singleton()->findOrMakeItem($object, $parameters);
            //provide a 0-quantity facade item if there is no such item in cart OR perhaps we should just store the product itself, and do away with the facade, as it might be unnecessary complication
            if (! $this->orderItem) {
                $className = $object->classNameForOrderItem();
                $this->orderItem = new $className($object->dataRecord, 0);
            }
        } elseif (is_a($object, Object::getCustomClass('OrderItem')) && $object->BuyableID) {
            $this->orderItem = $object;
        } else {
            user_error('EcomQuantityField: no/bad order item or buyable passed to constructor.', E_USER_WARNING);
        }
        if ($parameters) {
            $this->parameters = $parameters;
        }
    }

    /**
     * set classes for field.  you can add or "overwrite".
     *
     * @param array $newClasses
     * @param bool  $overwrite
     */
    public function setClasses(array $newClasses, $overwrite = false)
    {
        if ($overwrite) {
            $this->classes = array_merge($this->classes, $newClasses);
        } else {
            $this->classes = $newclasses;
        }
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * alias of OrderItem.
     *
     * @return OrderItem
     **/
    public function Item()
    {
        return $this->OrderItem();
    }

    /**
     * @return OrderItem
     **/
    public function OrderItem()
    {
        return $this->orderItem;
    }

    /**
     * @param $properties
     *
     * @return string (HTML)
     **/
    public function Field($properties = [])
    {
        $name = $this->orderItem->AJAXDefinitions()->TableID() . '_Quantity_SetQuantityLink';
        if (! isset(self::$tabindex[$name])) {
            self::$tabindex[$name] = count(self::$tabindex) + 1;
        }
        $attributes = [
            'type' => 'text',
            'class' => implode(' ', $this->classes),
            'name' => $name,
            'value' => $this->orderItem->Quantity ?: 0,
            'maxlength' => $this->maxLength,
            'size' => $this->fieldSize,
            'data-quantity-link' => $this->getQuantityLink(),
            'tabindex' => self::$tabindex[$name],
            'disabled' => 'disabled',
        ];
        $formfield = new FormField($name);

        return $formfield->createTag('input', $attributes);
    }

    /**
     * Used for storing the quantity update link for ajax use.
     *
     * @return string (HTML)
     */
    public function AJAXLinkHiddenField()
    {
        $name = $this->orderItem->AJAXDefinitions()->TableID() . '_Quantity_SetQuantityLink';
        if ($quantitylink = $this->getQuantityLink()) {
            $attributes = [
                'type' => 'hidden',
                'class' => 'ajaxQuantityField_qtylink',
                'name' => $name,
                'value' => $quantitylink,
            ];
            $formfield = new FormField($name);

            return $formfield->createTag('input', $attributes);
        }
    }

    /**
     * @return string (URLSegment)
     **/
    public function IncrementLink()
    {
        return ShoppingCart_Controller::add_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName, $this->parameters);
    }

    /**
     * @return string (URLSegment)
     **/
    public function DecrementLink()
    {
        return ShoppingCart_Controller::remove_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName, $this->parameters);
    }

    /**
     * @return string (HTML)
     **/
    public function forTemplate()
    {
        return $this->renderWith($this->template);
    }

    /**
     * @return string
     */
    protected function getQuantityLink()
    {
        return ShoppingCart_Controller::set_quantity_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName, $this->parameters);
    }

    /**
     * @return float
     */
    protected function Quantity()
    {
        if ($this->orderItem) {
            return floatval($this->orderItem->Quantity) - 0;
        }

        return 0;
    }
}
