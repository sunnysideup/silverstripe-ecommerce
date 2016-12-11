<?php

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 *
 * @package forms
 * @subpackage fields-gridfield
 */

class GridFieldPrintAllInvoicesButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{
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
            'printallinvoices',
            _t('TableListField.PRINT_ALL_INVOICES', 'Print all Invoices'),
            'printallinvoices',
            null
        );
        $button->setAttribute('data-icon', 'grid_print');
        $button->addExtraClass('no-ajax action_print_all_invoices');
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
        return array('printallinvoices');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'printallinvoices') {
            return $this->handlePrint($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'printallinvoices' => 'handlePrint',
        );
    }

    /**
     * Handle the print, for both the action button and the URL
      */
    public function handlePrint($gridField, $request = null)
    {
        $list = $gridField->getList();
        $gridField->setList($list);
        $al = ArrayList::create();
        foreach ($list as $order) {
            $al->push($order);
        }
        Requirements::clear();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        Requirements::themedCSS('OrderReport', 'ecommerce');
        Requirements::themedCSS('Order_Invoice', 'ecommerce');
        Requirements::themedCSS('Order_Invoice_Print_Only', 'ecommerce', 'print');
        $curr = Controller::curr();
        $curr->Orders = $al;
        return $curr->renderWith('PrintAllInvoices');
    }
}
