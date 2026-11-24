<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\OrderArchiver;
use Sunnysideup\Ecommerce\Cms\SalesAdmin;

/**
 * Adds an "Archive all older orders" button to a {@link GridField} of orders.
 *
 * The orders are moved straight to the Archived step without being written
 * (see {@link OrderArchiver}), so no order steps, emails, etc. are triggered.
 * Only orders placed before the cut-off date are archived, no matter what
 * is in the list.
 *
 * Class \Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldArchiveOldOrdersButton
 */
class GridFieldArchiveOldOrdersButton implements GridField_HTMLProvider, GridField_ActionProvider
{
    private const ACTION_NAME = 'archiveoldorders';

    /**
     * @var string
     */
    protected $targetFragment;

    /**
     * Y-m-d - orders placed before this day are archived.
     *
     * @var string
     */
    protected $cutoffDate;

    /**
     * @param string $cutoffDate     Y-m-d
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct(string $cutoffDate, string $targetFragment = 'buttons-before-left')
    {
        $this->cutoffDate = $cutoffDate;
        $this->targetFragment = $targetFragment;
    }

    public function getHTMLFragments($gridField)
    {
        $count = count($this->getIdsToArchive($gridField));
        if (0 === $count) {
            return [];
        }

        $title = 'Archive ' . $count . ' order' . (1 === $count ? '' : 's') . ' placed before ' . $this->getCutoffDateNice();
        $button = new GridField_FormAction(
            $gridField,
            self::ACTION_NAME,
            $title,
            self::ACTION_NAME,
            null
        );
        $button->addExtraClass('btn btn-outline-danger font-icon-trash');
        $button->setAttribute(
            'onclick',
            'if(!confirm(' . json_encode($title . '? They will be moved straight to the Archived step without any other processing.') . ')){event.stopPropagation();return false;}'
        );
        $button->setForm($gridField->getForm());

        return [
            $this->targetFragment => '<p class="grid-archive-button">' . $button->Field() . '</p>',
        ];
    }

    public function getActions($gridField)
    {
        return [self::ACTION_NAME];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (self::ACTION_NAME !== $actionName) {
            return;
        }

        if (! Permission::check('CMS_ACCESS_SalesAdminByOrderStep')) {
            Controller::curr()->httpError(403, 'You do not have permission to archive orders.');
        }

        $archived = OrderArchiver::archive_placed_before(
            $gridField->getList()->columnUnique(),
            $this->cutoffDate
        );
        SalesAdmin::reset_list_cache();
        if ([] !== $archived) {
            // the list in this request still has them, remove them from what is shown.
            $gridField->setList($gridField->getList()->exclude(['ID' => $archived]));
        }

        $count = count($archived);
        Controller::curr()->getResponse()->addHeader(
            'X-Status',
            rawurlencode($count . ' order' . (1 === $count ? '' : 's') . ' archived.')
        );
    }

    /**
     * @return int[]
     */
    protected function getIdsToArchive(GridField $gridField): array
    {
        return OrderArchiver::filter_ids_placed_before(
            $gridField->getList()->columnUnique(),
            $this->cutoffDate
        );
    }

    protected function getCutoffDateNice(): string
    {
        return date('j M Y', strtotime($this->cutoffDate));
    }
}
