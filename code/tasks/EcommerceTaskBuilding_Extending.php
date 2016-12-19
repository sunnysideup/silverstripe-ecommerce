<?php

/**
 * @authors: Nicolaas
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskBuilding_Extending extends BuildTask
{
    protected $title = 'Customise e-commerce';

    protected $description = 'Shows you the options for extending (customising) e-commerce.';

    public function run($request)
    {
        DB::alteration_message('<br /><br /><br /><br /><br /><br /><a href="/ecommerce/docs/en/CustomisationChart.yaml" target="_debug">view</a>.<br /><br /><br /><br /><br /><br />');
    }
}
