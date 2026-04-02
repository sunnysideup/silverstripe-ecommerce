<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;

use SilverStripe\Model\List\ArrayList;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 */
class GridFieldPrintAllPackingSlipsButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
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
    private static $packing_slip_bulk_printing_limit = 30;

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
            'printallpackingslips',
            _t('TableListField.PRINT_ALL_PACKING_SLIPS', 'Print all Packing Slips'),
            'printallpackingslips',
            null
        );
        $button->addExtraClass('action_print_all_packing_slips action btn btn-secondary no-ajax font-icon-down-circled action_export');
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
        return ['printallpackingslips'];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ('printallpackingslips' === $actionName) {
            return $this->handlePrintAllPackingSlips($gridField);
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
            'printallpackingslips' => 'handlePrintAllPackingSlips',
        ];
    }

    /**
     * Handle the print, for both the action button and the URL.
     *
     * @param mixed      $gridField
     * @param null|mixed $request
     */
    public function handlePrintAllPackingSlips($gridField, $request = null)
    {
        $limit = Config::inst()->get(GridFieldPrintAllPackingSlipsButton::class, 'packing_slip_bulk_printing_limit');
        $list = $gridField->getList()->limit($limit);
        $gridField->setList($list);
        $al = ArrayList::create();
        foreach ($list as $order) {
            $al->push($order);
        }
        Requirements::clear();
        Config::modify()->set(SSViewer::class, 'theme_enabled', true);
        Requirements::themedCSS('client/css/OrderReport');
        Requirements::themedCSS('client/css/Order_PackingSlip');
        $curr = Controller::curr();
        $curr->Orders = $al;

        return $curr->RenderWith('Sunnysideup\Ecommerce\Includes\PrintAllPackingSlips');
    }
}
