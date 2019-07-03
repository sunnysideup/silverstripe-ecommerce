<?php

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 *
 * @package forms
 * @subpackage fields-gridfield
 */

class GridFieldPrintAllPackingSlipsButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{

    /**
     *
     * @config
     * @var int
     */
    private static $packing_slip_bulk_printing_limit = 30;

    /**
     * HTML Fragment to render the field.
     *
     * @var string
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param array $exportColumns The columns to include in the export
     */
    public function __construct($targetFragment = "after")
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField,
            'printallpackingslips',
            _t('TableListField.PRINT_ALL_PACKING_SLIPS', 'Print all Packing Slips'),
            'printallpackingslips',
            null
        );
        $button->setAttribute('data-icon', 'grid_print');
        $button->addExtraClass('no-ajax action_print_all_packing_slips');
        $button->setForm($gridField->getForm());
        return array(
            $this->targetFragment => '<p class="grid-print-button">' . $button->Field() . '</p>',
        );
    }

    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array('printallpackingslips');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'printallpackingslips') {
            return $this->handlePrintAllPackingSlips($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'printallpackingslips' => 'handlePrintAllPackingSlips',
        );
    }

    /**
     * Handle the print, for both the action button and the URL
      */
    public function handlePrintAllPackingSlips($gridField, $request = null)
    {
        $limit = Config::inst()->get('GridFieldPrintAllPackingSlipsButton', 'packing_slip_bulk_printing_limit');
        $list = $gridField->getList()->limit($limit);
        $gridField->setList($list);
        $al = ArrayList::create();
        foreach ($list as $order) {
            $al->push($order);
        }
        Requirements::clear();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        Requirements::themedCSS('OrderReport', 'ecommerce');
        Requirements::themedCSS('Order_PackingSlip', 'ecommerce');
        $curr = Controller::curr();
        $curr->Orders = $al;
        return $curr->renderWith('PrintAllPackingSlips');
    }
}
