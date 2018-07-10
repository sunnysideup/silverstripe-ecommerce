<?php


/**
 * This field shows the admin (and maybe the customer) where the Order is at (which orderstep).
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStepField extends DatalessField
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @param string $name
     * @param Order  $order
     * @param Member $member
     */
    public function __construct($name, Order $order, Member $member = null)
    {
        if (!$member) {
            $member = $order->Member();
        }
        if (!$member) {
            $member = new Member();
        }
        $orderSteps = OrderStep::get();
        $where = '"HideStepFromCustomer" = 0';
        $currentStep = $order->CurrentStepVisibleToCustomer();
        if ($member->IsShopAdmin()) {
            $currentStep = $order->MyStep();
        } else {
            $currentStep = $order->CurrentStepVisibleToCustomer();
            $orderSteps = $orderSteps
                ->filter(array('HideStepFromCustomer' => 0));
        }
        $future = false;
        $html = '
        <div class="orderStepField">
            <ol>';
        if ($orderSteps->count()) {
            foreach ($orderSteps as $orderStep) {
                if ($orderStep->HideFromEveryone()) {
                    continue;
                }
                $description = '';
                if ($member->IsShopAdmin()) {
                    if ($orderStep->Description) {
                        $description = ' title="'.Convert::raw2att($orderStep->Description).'" ';
                    }
                }
                $class = '';
                if ($orderStep->ID == $currentStep->ID) {
                    $future = true;
                    $class .= ' current';
                } elseif ($future) {
                    $class .= ' todo';
                } else {
                    $class .= ' done';
                }
                $html .= '<li class="'.$class.'" '.$description.'>'.$orderStep->Title.'</li>';
            }
        } else {
            $html .= 'no steps';
        }
        $html .= '</ol><div class="clear"></div></div>';
        $this->content = $html;
        Requirements::themedCSS('OrderStepField', 'ecommerce');
        parent::__construct($name);
    }

    /**
     * standard SS method.
     *
     * @param array $properties
     *
     * @return HTML
     */
    public function FieldHolder($properties = array())
    {
        return is_object($this->content) ? $this->content->forTemplate() : $this->content;
    }

    /**
     * standard SS method.
     *
     * @param array $properties
     *
     * @return HTML
     */
    public function Field($properties = array())
    {
        return $this->FieldHolder();
    }

    /**
     * Sets the content of this field to a new value.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Synonym of {@link setContent()} so that LiteralField is more compatible with other field types.
     *
     * @param mixed
     */
    public function setValue($value)
    {
        return $this->setContent($value);
    }

    /**
     * standard SS method.
     *
     * @return Field
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);

        return $clone;
    }
}
