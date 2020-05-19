<?php

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskBuildingModel extends BuildTask
{
    protected $title = 'View the E-commerce Model';

    protected $description = '
        Shows the complete data model';

    public function run($request)
    {
        DB::alteration_message('<br /><br /><br /><br /><br /><br /><a href="/ecommerce/docs/en/DataModel.png" target="_debug">view</a>.<br /><br /><br /><br /><br /><br />');
    }
}

