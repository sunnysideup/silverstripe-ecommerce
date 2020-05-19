<?php

namespace Sunnysideup\Ecommerce\Tasks;

use BuildTask;
use Director;


/**
 * This class reviews all of the static configurations in e-commerce for review
 * (a) which configs are set, but not required
 * (b) which configs are required, but not set
 * (c) review of set configs.
 *
 * @TODO: compare to default
 *
 * shows you the link to remove the current cart
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskAPIandMore extends BuildTask
{
    /**
     * Standard (required) SS variable for BuildTasks.
     *
     * @var string
     */
    protected $title = 'Useful Links for the e-commerce project (including full API)';

    /**
     * Standard (required) SS variable for BuildTasks.
     *
     * @var string
     */
    protected $description = 'Provides a bunch of other links of use when developing e-commerce sites.';

    /**
     * Standard (required) SS method, runs buildtask.
     */
    public function run($request)
    {
        $baseURL = Director::baseURL();
        echo <<<html
		<h2>usefull links</h2>
		<ul>
			<li><a href="{$baseURL}ecommerce/docs/api/">API for the e-commerce project</a></li>
			<li><a href="{$baseURL}ecommerce/docs/README.md">Information on how to update the API</a></li>
			<li><a href="http://www.silverstripe-ecommerce.com/">demo site with lots more help and links</a></li>
		</ul>
html;
    }
}

