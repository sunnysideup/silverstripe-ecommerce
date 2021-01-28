<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\View\ViewableData;

class Debug extends ViewableData

{

    protected $rootGroup = null;
    protected $rootGroupController = null;

    function __construct($rootGroupController, $rootGroup)
    {
        $this->rootGroupController = $rootGroupController;
        $this->rootGroup = $rootGroup;
    }

    public function print()
    {
        echo $this->renderWith('Sunnysideup/Ecommerce/Includes/ProductsAndGroupsDebug');
    }

    public function RootGroupController()
    {
        return $this->getRootGroupContoller();
    }

    public function getRootGroupContoller()
    {
        return $this->rootGroupController;
    }

}
