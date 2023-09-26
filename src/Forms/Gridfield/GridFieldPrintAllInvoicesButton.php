<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
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
     * @config
     *
     * @var int
     */
    private static $invoice_bulk_printing_limit = 30;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = 'after')
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field.
     *
     * @param mixed $gridField
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

        return [
            $this->targetFragment => '<p class="grid-print-button">' . $button->Field() . '</p>',
        ];
    }

    /**
     * export is an action button.
     *
     * @param mixed $gridField
     */
    public function getActions($gridField)
    {
        return ['printallinvoices'];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ('printallinvoices' === $actionName) {
            return $this->handlePrint($gridField);
        }
    }

    /**
     * it is also a URL.
     *
     * @param mixed $gridField
     */
    public function getURLHandlers($gridField)
    {
        return [
            'printallinvoices' => 'handlePrint',
        ];
    }

    /**
     * Handle the print, for both the action button and the URL.
     *
     * @param mixed      $gridField
     * @param null|mixed $request
     */
    public function handlePrint($gridField, $request = null)
    {
        $limit = Config::inst()->get(GridFieldPrintAllInvoicesButton::class, 'invoice_bulk_printing_limit');
        $list = $gridField->getList()->limit($limit);
        $gridField->setList($list);
        $al = ArrayList::create();
        foreach ($list as $order) {
            $al->push($order);
        }
        Requirements::clear();
        Config::modify()->set(SSViewer::class, 'theme_enabled', true);
        Requirements::themedCSS('client/css/OrderReport');
        Requirements::themedCSS('client/css/Order_Invoice');
        Requirements::themedCSS('client/css/Order_Invoice_Print_Only', 'print');
        $curr = Controller::curr();
        $curr->Orders = $al;

        return $curr->RenderWith('Sunnysideup\Ecommerce\Includes\PrintAllInvoices');
    }
}
