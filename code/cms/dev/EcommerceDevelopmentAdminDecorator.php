<?php

/**
 * EcommerceDevelopmentAdminDecorator adds extra functionality to the DevelopmentAdmin
 * It creates a developer view (as in www.mysite.com/dev/) specifically for ecommerce.
 *
 * @authors: Jeremy,
 *
  * @package: ecommerce
 * @sub-package: model
 *
 **/
class EcommerceDevelopmentAdminDecorator extends Extension{

	/**
	 * handles ecommerce request or provide options to run request in the form of HTML output.
	 *@param SS_HTTPRequest
	 *@return HTML
	 **/

	function ecommerce($request) {
		if(Director::is_cli()) {
			$da = Object::create('EcommerceDatabaseAdmin');
			return $da->handleRequest($request);
		}
		else {
			$renderer = Object::create('DebugView');
			$renderer->writeHeader();
			$renderer->writeInfo("Ecommerce Development Tools", Director::absoluteBaseURL());
			echo "<div style=\"margin: 0 2em\">";

			$da = Object::create('EcommerceDatabaseAdmin');
			return $da->handleRequest($request);

			echo "</div>";
			$renderer->writeFooter();
		}
	}

}


