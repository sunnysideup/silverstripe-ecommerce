<?php

/**
 * Get examples for building templates.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTemplateTestTask extends BuildTask{

	protected $title = "Get help with building templates";

	protected $description = "Shows you some of the variables and controls you can use in your templates.";

	function run($request){
		DB::alteration_message("<br /><br /><br /><br /><br /><br /><a href=\"/ecommercetemplatetest/?flush=all\" target=\"_debug\">click here to view template test page</a>.<br /><br /><br /><br /><br /><br />");
	}

}

