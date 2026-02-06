<?php

namespace Sunnysideup\Ecommerce\Traits;

use SilverStripe\ORM\Connect\MySQLSchemaManager;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;

trait SearchTableTrait
{
    private static $create_table_options = [
        MySQLSchemaManager::ID => 'ENGINE=MyISAM',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->dataFieldByName('Data')
            ->setDescription(
                'The length of this field is limited to 2048 characters.
                You are currently using ' . strlen($this->Data) . ' characters.'
            );

        return $fields;
    }

    /**
     * link to edit the record.
     *
     * @param null|string $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }
}
