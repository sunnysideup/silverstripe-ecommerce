<?php

namespace Sunnysideup\Ecommerce\Tasks;

use BuildTask;
use DB;


/**
 * shows you the link to remove the current cart.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskCartManipulationCurrent extends BuildTask
{
    protected $title = 'Clear the current Cart';

    protected $description = '
        Removes the cart that is currently in memory (session) for the currrent user.
        It does not delete the order itself.';

    public function run($request)
    {
        DB::alteration_message('<br /><br /><br /><br /><br /><br /><a href="/shoppingcart/clear/" target="_debug">click here to clear the current cart from your session</a>.<br /><br /><br /><br /><br /><br />');
    }
}

