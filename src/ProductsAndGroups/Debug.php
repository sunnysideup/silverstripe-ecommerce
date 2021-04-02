<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\View\ViewableData;

class Debug extends ViewableData
{
    protected $rootGroup;

    protected $rootGroupController;

    public function __construct($rootGroupController, $rootGroup)
    {
        parent::__construct();
        $this->rootGroupController = $rootGroupController;
        $this->rootGroup = $rootGroup;
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
