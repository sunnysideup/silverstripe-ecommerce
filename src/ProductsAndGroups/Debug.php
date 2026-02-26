<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Model\ModelData;

class Debug extends ModelData
{
    protected $rootGroup;

    protected $rootGroupController;

    public function __construct($rootGroupController, $rootGroup)
    {
        parent::__construct();
        $this->rootGroupController = $rootGroupController;
        $this->rootGroup = $rootGroup;
        if (! $this->rootGroup) {
            user_error('Please specify rootGroup');
        }
    }

    public function print()
    {
        echo $this->renderWith('Sunnysideup/Ecommerce/Includes/ProductsAndGroupsDebug');
    }

    public function getRootGroup()
    {
        return $this->rootGroup;
    }

    public function getRootGroupController()
    {
        return $this->rootGroupController;
    }
}
