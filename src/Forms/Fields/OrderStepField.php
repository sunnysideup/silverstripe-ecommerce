<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;







use Sunnysideup\Ecommerce\Model\Order;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use SilverStripe\Core\Convert;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\DatalessField;




/**
 * This field shows the admin (and maybe the customer) where the Order is at (which orderstep).
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms

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
        if (! $member) {
            $member = $order->Member();
        }
        if (! $member) {
            $member = new Member();
        }
        $orderSteps = OrderStep::get();
        // $where = '"HideStepFromCustomer" = 0';
        $currentStep = $order->CurrentStepVisibleToCustomer();
        if ($member->IsShopAdmin()) {
            $currentStep = $order->MyStep();
        } else {
            $currentStep = $order->CurrentStepVisibleToCustomer();
            $orderSteps = $orderSteps
                ->filter(['HideStepFromCustomer' => 0]);
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
                        $description = ' title="' . Convert::raw2att($orderStep->Description) . '" ';
                    }
                }
                $class = '';
                if ($orderStep->ID === $currentStep->ID) {
                    $future = true;
                    $class .= ' current';
                } elseif ($future) {
                    $class .= ' todo';
                } else {
                    $class .= ' done';
                }
                $html .= '<li class="' . $class . '" ' . $description . '><a href="' . $orderStep->CMSEditLink() . '">' . $orderStep->Title . '</a></li>';
            }
        } else {
            $html .= 'no steps';
        }
        $html .= '</ol><div class="clear"></div></div>';
        $this->content = $html;
        Requirements::themedCSS('sunnysideup/ecommerce: OrderStepField', 'ecommerce');
        parent::__construct($name);
    }

    /**
     * standard SS method.
     *
     * @param array $properties
     *
     * @return HTML
     */
    public function FieldHolder($properties = [])
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
    public function Field($properties = [])
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
     * @param mixed $value
     */
    public function setValue($value, $data = NULL)
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
