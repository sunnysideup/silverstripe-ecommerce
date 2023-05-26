<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * Adds all members, who have bought something, to the customer group.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskRemoveSuperfluousLinksInProductProductGroups extends BuildTask
{
    protected $title = 'Delete superfluous entries in Product_ProductGroups';

    protected $description = 'Look at all the links in Product_ProductGroups and removes non-existing entries.';

    protected $verbose = false;

    public function setVerbose(bool $b)
    {
        $this->verbose = $b;

        return $this;
    }

    public function run($request)
    {
        if ($this->verbose) {
            DB::alteration_message('Before: ' . DB::query('COUNT(ID) FROM Product_ProductGroups;')->value());
        }
        DB::query('
            DELETE T1 FROM Product_ProductGroups AS T1
                LEFT JOIN Product ON Product.ID = ProductID
                LEFT JOIN ProductGroup ON ProductGroup.ID = ProductGroupID
            WHERE Product.ID IS NULL OR ProductGroup.ID IS NULL
        ');
        if ($this->verbose) {
            DB::alteration_message('After: ' . DB::query('COUNT(ID) FROM Product_ProductGroups;')->value());
        }
    }
}
