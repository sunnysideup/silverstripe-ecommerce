<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Model\ModelData;

class Debug extends ModelData
{
    public function __construct(protected $rootGroupController, protected $rootGroup)
    {
        parent::__construct();
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
