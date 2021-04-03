<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\NumericField;
use SilverStripe\View\HTML;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;
use Sunnysideup\Ecommerce\Model\OrderItem;

/**
 * @Description: A links-based field for increasing, decreasing and setting a order item quantity
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class EcomQuantityField extends NumericField
{
    /**
     * @var OrderItem
     */
    protected $orderItem;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var array
     */
    protected $classes = ['ajaxQuantityField'];

    /**
     * max length in digits.
     *
     * @var int
     */
    protected $maxLength = 3;

    /**
     * max length in digits.
     *
     * @var int
     */
    protected $fieldSize = 3;

    /**
     * @var string
     */
    protected $template = EcomQuantityField::class;

    /**
     * the tabindex for the form field
     * we use this so that you can tab through all the
     * quantity fields without disruption.
     * It is saved like this: "FieldName (String)" => tabposition (int).
     *
     * @var array
     */
    private static $tabindex = [];

    /**
     * @param buyable    $parameters - the buyable / OrderItem
     * @param null|array $parameters - parameters
     * @param mixed      $object
     */
    public function __construct($object, $parameters = [])
    {
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomQuantityField.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
        if ($object instanceof BuyableModel) {
            $this->orderItem = ShoppingCart::singleton()->findOrMakeItem($object, $parameters);
            //provide a 0-quantity facade item if there is no such item in cart OR perhaps we should just store the product itself, and do away with the facade, as it might be unnecessary complication
            if (! $this->orderItem) {
                $className = $object->classNameForOrderItem();
                $this->orderItem = new $className($object->dataRecord, 0);
            }
        } elseif (is_a($object, EcommerceConfigClassNames::getName(OrderItem::class)) && $object->BuyableID) {
            $this->orderItem = $object;
        } else {
            user_error('EcomQuantityField: no/bad order item or buyable passed to constructor.', E_USER_WARNING);
        }
        if ($parameters) {
            $this->parameters = $parameters;
        }
        $name = $this->orderItem->AJAXDefinitions()->TableID() . '_Quantity_SetQuantityLink';

        parent::__construct($name, $title = '');
    }

    /**
     * set classes for field.  you can add or "overwrite".
     *
     * @param bool $overwrite
     */
    public function setClasses(array $newClasses, $overwrite = false): self
    {
        $this->classes = $overwrite ? array_merge($this->classes, $newClasses) : $newClasses;

        return $this;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * alias of OrderItem.
     *
     * @return OrderItem
     */
    public function Item()
    {
        return $this->OrderItem();
    }

    /**
     * @return OrderItem
     */
    public function OrderItem()
    {
        return $this->orderItem;
    }

    /**
     * @param array $properties
     *
     * @return string (HTML)
     */
    public function Field($properties = [])
    {
        $name = $this->getName();
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

        return HTML::createTag('input', $attributes);
    }

    /**
     * Used for storing the quantity update link for ajax use.
     */
    public function AJAXLinkHiddenField(): string
    {
        $name = $this->orderItem->AJAXDefinitions()->TableID() . '_Quantity_SetQuantityLink';
        if ($quantitylink = $this->getQuantityLink()) {
            $attributes = [
                'type' => 'hidden',
                'class' => 'ajaxQuantityField_qtylink',
                'name' => $name,
                'value' => $quantitylink,
            ];

            return HTML::createTag('input', $attributes);
        }

        return '';
    }

    /**
     * @return string (URLSegment)
     */
    public function IncrementLink(): string
    {
        return ShoppingCartController::add_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName, $this->parameters);
    }

    /**
     * @return string (URLSegment)
     */
    public function DecrementLink(): string
    {
        return ShoppingCartController::remove_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName, $this->parameters);
    }

    /**
     * @return string (HTML)
     */
    public function forTemplate(): string
    {
        return $this->renderWith($this->template);
    }

    protected function getQuantityLink(): string
    {
        return ShoppingCartController::set_quantity_item_link($this->orderItem->BuyableID, $this->orderItem->BuyableClassName, $this->parameters);
    }

    protected function Quantity(): float
    {
        if ($this->orderItem) {
            return floatval($this->orderItem->Quantity) - 0;
        }

        return 0;
    }
}
