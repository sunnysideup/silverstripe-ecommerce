<?php

/**
 * This class reviews all of the static configurations in e-commerce for review
 * (a) which configs are set, but not required
 * (b) which configs are required, but not set
 * (c) review of set configs
 *
 * @TODO: compare to default
 *
 * shows you the link to remove the current cart
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceTaskCheckConfiguration extends BuildTask{

	/**
	 * Default Location for Configuration File
	 * @var String
	 */
	protected $defaultLocation = "ecommerce/_config/ecommerce.yml";

	/**
	 * Standard (required) SS variable for BuildTasks
	 * @var String
	 */
	protected $title = "Check Configuration";

	/**
	 * Standard (required) SS variable for BuildTasks
	 * @var String
	 */
	protected $description = "Runs through all static configuration for review.";

	/**
	 * Array of definitions - set like this:
	 * ClassName
	 * 		VariableName: Description
	 * @var Array
	 */
	protected $definitions = array();

	/**
	 * Array of definitions Header - set like this:
	 * HEADER TITLE
	 * 		ClassName
	 * @var Array
	 */
	protected $definitionsHeaders = array();

	/**
	 * Array of defaults - set like this:
	 * ClassName
	 * 		VariableName: Default Variable Value
	 * @var Array
	 */
	protected $defaults = array();


	/**
	 * Array of configs - set like this:
	 * ClassName
	 * 		VariableName: VariableValue
	 * @var Array
	 */
	protected $configs = array();

	/**
	 * which values are derived from DB
	 * ClassName
	 * 		VariableName: TRUE | FALSE
	 * @var Array
	 */
	protected $databaseValues = array();

	/**
	 * set in default yml, but not customised.
	 * ClassName
	 * 		VariableName: TRUE | FALSE
	 * @var Array
	 */
	protected $customisedValues = array();

	/**
	 * Other configs
	 * ClassName
	 * 		VariableName: TRUE | FLASE
	 * @var Array
	 */
	protected $otherConfigs = array();

	/**
	 * Array of classes (partially) missing in configs.
	 * VariableName: VariableName
	 *  @var Array
	 */
	protected $missingClasses = array();



	/**
	 * Standard (required) SS method, runs buildtask
	 */
	function run($request){
		$definitionsObject = EcommerceConfigDefinitions::create();
		$this->definitions = $definitionsObject->Definitions();
		$this->definitionsHeaders = $definitionsObject->GroupDefinitions();
		$configsObject = EcommerceConfig::create();
		$this->configs = $configsObject->getCompleteDataSet();
		$this->defaults = $this->getDefaultValues();
		if($this->definitions) {
			if($this->configs) {
				if($this->defaults) {
					$this->checkFiles();
					$this->configsNotSet();
					$this->classesThatDoNotExist();
					$this->definitionsNotSet();
					$this->addEcommerceDBConfigToConfigs();
					$this->addOtherValuesToConfigs();
					$this->addPages();
					$this->orderSteps();
					$this->checkoutAndModifierDetails();
					$this->getAjaxDefinitions();
					$this->definedConfigs();
					$this->checkGEOIP();
				}
				else {
					DB::alteration_message("ERROR: could not find any defaults", "deleted");
				}
			}
			else {
				DB::alteration_message("ERROR: could not find any configs", "deleted");
			}
		}
		else {
			DB::alteration_message("ERROR: could not find any definitions", "deleted");
		}

	}

	/**
	 * Check what files is being used
	 */
	protected function checkFiles(){
		$configsObject = EcommerceConfig::create();
		DB::alteration_message("<h2>Files Used</h2>");
		$files = implode(", ", $configsObject->fileLocations());
		global $project;
		$baseFolder = Director::baseFolder();
		$projectFolder = $project."/_config";
		$baseAndProjectFolder = $baseFolder."/".$projectFolder;
		$file = "ecommerce.yml";
		$projectFolderAndFile = $projectFolder."/".$file;
		$fullFilePath = $baseFolder."/".$projectFolderAndFile;
		$defaultFileFullPath = Director::baseFolder()."/".$this->defaultLocation;
		DB::alteration_message("
			Current files used: <strong style=\"color: darkRed\">".$files."</strong>,
			unless stated otherwise, all settings can be edited in these file(s).",
			"created"
		);
		if(!file_exists($baseAndProjectFolder)) {
			mkdir($baseAndProjectFolder);
		}
		if(!file_exists($fullFilePath)) {
			copy($defaultFileFullPath, $fullFilePath);
			DB::alteration_message("We have created a new configuration file for you.", "created");
		}
		if($files == $this->defaultLocation) {
			if(file_exists($fullFilePath)) {
				DB::alteration_message("A customisable configuration file exists here: $projectFolderAndFile, you should add the following to your config.yml file:
<pre>
EcommerceConfig:
  folder_and_file_locations:
    - \"$projectFolderAndFile\"
</pre>", "created");
			}
		}
	}

	/**
	 * Work out items set in the configuration but not set in the config file.
	 */
	protected function definitionsNotSet(){
		echo "<h2>Set in configs but not defined</h2>";
		$allOK = true;
		foreach($this->configs as $className => $setting) {
			if(!isset($this->definitions[$className])) {
				$allOK = false;
				$this->missingClasses[$className] = $className;
				DB::alteration_message("$className", "deleted");
			}
			else {
				$classConfigs = $this->configs[$className];
				foreach($classConfigs as $key => $classConfig) {
					if(!isset($this->definitions[$className][$key])) {
						$allOK = false;
						DB::alteration_message("$className.$key", "deleted");
					}
				}
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
		else {
			DB::alteration_message("Recommended course of action: remove from your config as these are superfluous!", "edited");
		}
	}


	/**
	 * Work out items set in the configuration but not set in the config file.
	 */
	protected function classesThatDoNotExist(){
		echo "<h2>Classes that do not exist</h2>";
		$allOK = true;
		foreach($this->configs as $className => $setting) {
			if(!class_exists($className)) {
				$allOK = false;
				DB::alteration_message("$className", "deleted");
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
		else {
			DB::alteration_message("Recommended course of action: remove from your config file and review if any other action needs to be taken.", "edited");
		}

	}


	/**
	 * Work out items set in the definitions but not set in the config file.
	 */
	protected function configsNotSet(){
		echo "<h2>Defined variables not set in configs ...</h2>";
		$allOK = true;
		//print_r($this->configs["EcommercePayment"]);
		foreach($this->definitions as $className => $setting) {
			if(!isset($this->configs[$className])) {
				DB::alteration_message("No settings found for $className in /ecommerce/_config/config.yml", "deleted");
			}
			else {
				$classConfigs = $this->definitions[$className];
				foreach($classConfigs as $key => $classConfig) {
					if(!isset($this->configs[$className][$key])) {
						$this->customisedValues[$className][$key] = false;
						//fallback to Configs...

					}
					else {
						$this->customisedValues[$className][$key] = false;
					}
					if(!isset($this->configs[$className][$key])) {
						DB::alteration_message(" - $className.$key NOT SET in /ecommerce/_config/config.yml", "deleted");
						$allOK = false;
					}
					else {
						//$this->configs[$className][$key] = EcommerceConfig::get($className, $key);
						//if(!$this->configs[$className][$key]) {
							//DB::alteration_message(" - $className.$key exists, set to FALSE / [EMPTRY STRING]", "edited");
						//}
					}
				}
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
		else {
			DB::alteration_message("Recommended course of action: add the above configs to your mysite/_config/ecommerce.yml file if you required them.", "edited");
		}
	}


	/**
	 * Work out items set in the definitions but not set in the config file.
	 */
	protected function definedConfigs(){
		$htmlHeader = "
		<style>
			body {margin-left: 300px!important;}
			h2 {padding-top: 2em;margin-bottom: 0; padding-bottom: 0;}
			th[scope='col'] {text-align: left; border-bottom: 3px solid #ccdef3;padding-top: 40px;}
			td {vertical-align: top; border-left: 1px solid #d7d7d7; border-bottom: 1px solid #d7d7d7; padding: 10px; width: 47%;}
			/** headings **/
			td span.spanTitle {color: #002137; font-weight: 900; display: block; padding-left: 10px; padding-bottom: 5px;}
			.ecommerceConfigHeadings th, h2 {
				font-size: 1.2em;
				padding-bottom: 5px;
				color: #002137;
			}
			td span {color: #000; font-size: 0.8em; display: block; padding-left: 10px; }
			.sameConfig {color: #000;}
			.newConfig pre:first-of-type{color: #000; background-color: yellow;}
			.newConfig pre:first-of-type { }
			.newConfig pre:nth-of-type(2) { }
			#TOC {
				position: fixed;
				top: -15px;
				bottom: -20px;
				color: #fff;
				background-color: #000;
				width: 270px;
				left: 0px;
				padding-top: 15px;
				z-index: 10000;
				overflow: auto;
				padding-bottom: 20px;
			}
			#TOC ul {
				list-style-type: none;
			}
			#TOC li {
				line-height: 1.3;
				font-size: 80%;
				font-weight: 900;
				height: auto;
				list-style-type: none;
			}
			#TOC a {
				color: #fff;
				text-decoration: none;
				font-size: 85%;
				font-weight: 900;
				margin-left: -10px;
			}
			#TOC a:hover {
				color: #7da4be;
			}
			/* not sure why we needed this ...
			#TaskHolder, #EcommerceDatabaseAdmin, .info h1, .info h3, .info a:first-of-type  {
				margin-left: 280px !important;
			}
			*/
			.info h1, .info h3, .info a {
				padding-left: 30px;
			}
			a.backToTop {display: block; font-size: 0.7em; float: right;}
			td.newConfig {}
			table td pre, table td sub {white-space:pre-wrap; font-size: 1em; font-weight: bold;margin: 3px; padding: 3px;}
			table td sub {font-weight: normal; font-size: 77%;}

			li pre {width: auto;}
		</style>
		";
		$htmlTable = "
		<table summary=\"list of configs\">
		";
		$oldClassName = "";
		$htmlTOC = "<div id=\"TOC\"><ul>";
		$count = 0;
		$oldHeaderOfGroup = "";
		$newHeader = "";
		$completedListOfClasses = array();
		foreach($this->definitionsHeaders as $headerOfGroup => $classesArray) {
			if($headerOfGroup == "OTHER") {
				$classesArray = array_keys(array_diff_key($this->configs, $completedListOfClasses));
			}
			foreach($classesArray as $className) {
				$completedListOfClasses[$className] = $className;
				if(!isset($this->configs[$className])) {
					$this->configs[$className] = array();
				}
				$settings = $this->configs[$className];
				$count++;
				if(in_array($className, $classesArray)) {
					$newHeader = $headerOfGroup;
				}
				if($oldHeaderOfGroup != $newHeader) {
					$oldHeaderOfGroup = $headerOfGroup;
					$htmlTOC .= "</ul><li class=\"header\">$headerOfGroup</li><ul>";
				}

				$htmlTOC .= "<li><a href=\"#$className\">$count. $className</a></li>";
				if($className != $oldClassName) {
					$htmlTable .= "<tr  class='ecommerceConfigHeadings' id=\"$className\"><th colspan=\"2\" scope=\"col\">
					$count. $className ($newHeader)
					<a class=\"backToTop\" href=\"#TaskHolder\">top</a>
					</th></tr>";
					$oldClassName = $className;
				}
				if(is_array($settings)) {
					foreach($settings as $key => $classConfig) {
						$configError = "";
						$class = "";
						$hasDefaultvalue = false;
						$isDatabaseValues = isset($this->databaseValues[$className][$key]) ? $this->databaseValues[$className][$key] : false;
						$isOtherConfigs = isset($this->otherConfigs[$className][$key]) ? $this->otherConfigs[$className][$key] : false;
						$isCustomisedValues = isset($this->customisedValues[$className][$key]) ? $this->customisedValues[$className][$key] : false;
						if(!isset($this->defaults[$className][$key])) {
							//DB::alteration_message("Could not retrieve default value for: $className $key", "deleted");
						}
						else {
							$defaultValue = print_r($this->defaults[$className][$key], 1);
							$hasDefaultvalue = true;
						}
						$manuallyAddedValue = print_r($this->configs[$className][$key], 1);
						$actualValueRaw = EcommerceConfig::get($className, $key);
						//if(!$actualValueRaw && $manuallyAddedValue) {
						//	$actualValueRaw = $manuallyAddedValue;
						//}

						$actualValue = print_r($actualValueRaw, 1);
						if($defaultValue === $manuallyAddedValue && $isCustomisedValues) {
							$configError .= "This is a superfluous entry in your custom config as the default value is the same.";
						}
						$hasDefaultvalue = true;
						if($defaultValue === $actualValue) {
							$class .= "sameConfig";
							$defaultValue = "";
							$hasDefaultvalue = false;
						}
						else {
							$class .= " newConfig";
						}
						$actualValue = $this->turnValueIntoHumanReadableValue($actualValue);
						if($hasDefaultvalue) {
							$defaultValue = $this->turnValueIntoHumanReadableValue($defaultValue);
						}

						if(!isset($this->definitions[$className][$key])) {
							$description = "<span style=\"color: red; font-weight: bold\">ERROR: no longer required in configs!</span>";
						}
						else {
							$description = $this->definitions[$className][$key];
							$description .= $this->specialCases($className, $key, $actualValue);
						}
						$defaultValueHTML = "";
						if($defaultValue && !$isOtherConfigs) {
							$defaultValueHTML = "<sub>default:</sub><pre>$defaultValue</pre>";
						}
						if($configError) {
							$configError = "<span style=\"color: red; font-size: 10px;\">$configError</span>";
						}
						$sourceNote = "";
						if($isDatabaseValues) {
							$sourceNote = "<span>Values are set in the database using the CMS.</span>";
						}
						$htmlTable .= "<tr>
				<td>
					<span class='spanTitle'>$key</span>
					<span>$description</span>
					$sourceNote
				</td>
				<td class=\"$class\">
					<pre>$actualValue</pre>
					$defaultValueHTML
					$configError
				</td>
			</tr>";
					}
				}
			}
		}
		$htmlEnd = "
		</table>
		<h2>--- THE END ---</h2>
		";
		$htmlTOC .= "</ul></div>";
		echo $htmlHeader.$htmlTOC.$htmlTable.$htmlEnd;
	}

	protected function getDefaultValues(){
		require_once 'thirdparty/spyc/spyc.php';
		$fixtureFolderAndFile = Director::baseFolder()."/".$this->defaultLocation;
		$parser = new Spyc();
		return $parser->loadFile($fixtureFolderAndFile);
	}

	/**
	 * Adding EcommerceDBConfig values
	 */
	protected function addEcommerceDBConfigToConfigs(){
		$ecommerceDBConfig = EcommerceDBConfig::current_ecommerce_db_config();
		$fields = $ecommerceDBConfig->fieldLabels();
		if($fields) {
			foreach($fields as $field => $description) {
				if($field != "Title" && $field != "UseThisOne") {
					$defaultsDefaults = $ecommerceDBConfig->stat("defaults");
					$this->definitions["EcommerceDBConfig"][$field] = "$description. <br />see: <a href=\"/admin/shop/EcommerceDBConfig/EditForm/field/EcommerceDBConfig/item/".$ecommerceDBConfig->ID."/edit\">Ecommerce Configuration</a>";
					$this->configs["EcommerceDBConfig"][$field] = $ecommerceDBConfig->$field;
					$this->databaseValues["EcommerceDBConfig"][$field] = true;
					$this->defaults["EcommerceDBConfig"][$field] = isset($defaultsDefaults[$field]) ? $defaultsDefaults[$field] : "no default set";
					$imageField = $field."ID";
					if(isset($ecommerceDBConfig->$imageField)) {
						if($image = $ecommerceDBConfig->$field()) {
							if($image->exists() && is_a($image, Object::getCustomClass("Image")) ) {
								$this->configs["EcommerceDBConfig"][$field] = "[Image]  --- <img src=\"".$image->Link()."\" />";
								$this->databaseValues["EcommerceDBConfig"][$field] = true;
							}
						}
					}
				}
			}
		}
	}


	protected function addOtherValuesToConfigs(){

		$this->definitions["Email"]["admin_email_address"] = "Default administrator email. <br />SET USING Email::\$admin_email = \"bla@ta.com\" in the _config.php FILES";
		$this->configs["Email"]["admin_email_address"] = Config::inst()->get("Email", "admin_email");
		$this->defaults["Email"]["admin_email_address"] = "[no default set]";
		$this->otherConfigs["Email"]["admin_email_address"] = true;

		$siteConfig = SiteConfig::current_site_config();
		$this->definitions["SiteConfig"]["website_title"] = "The name of the website. <br />see: <a href=\"/admin/settings/\">site configuration</a>.";
		$this->configs["SiteConfig"]["website_title"] = $siteConfig->Title;
		$this->defaults["SiteConfig"]["website_title"] = "[no default set]";
		$this->otherConfigs["SiteConfig"]["website_title"] = true;

		$this->definitions["SiteConfig"]["website_tagline"] = "The subtitle or tagline of the website. <br />see: <a href=\"/admin/settings/\">site configuration</a>.";
		$this->configs["SiteConfig"]["website_tagline"] = $siteConfig->Tagline;
		$this->defaults["SiteConfig"]["website_tagline"] = "[no default set]";
		$this->otherConfigs["SiteConfig"]["website_tagline"] = true;

	}

	protected function addPages(){


		if($checkoutPage = CheckoutPage::get()->First()) {
			$this->getPageDefinitions($checkoutPage);
			$this->definitions["Pages"]["CheckoutPage"] = "Page where customers finalise (checkout) their order. This page is required.<br />".($checkoutPage ? "<a href=\"/admin/pages/edit/show/".$checkoutPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["CheckoutPage"] = $checkoutPage ? "view: <a href=\"".$checkoutPage->Link()."\">".$checkoutPage->Title."</a><br />".$checkoutPage->configArray : " NOT CREATED!";
			$this->defaults["Pages"]["CheckoutPage"] = $checkoutPage ? $checkoutPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["CheckoutPage"] = true;
		}

		if($orderConfirmationPage = OrderConfirmationPage::get()->First()) {
			$this->getPageDefinitions($orderConfirmationPage);
			$this->definitions["Pages"]["OrderConfirmationPage"] = "Page where customers review their order after it has been placed. This page is required.<br />".($orderConfirmationPage ? "<a href=\"/admin/pages/edit/show/".$orderConfirmationPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["OrderConfirmationPage"] = $orderConfirmationPage ? "view: <a href=\"".$orderConfirmationPage->Link()."\">".$orderConfirmationPage->Title."</a><br />".$orderConfirmationPage->configArray: " NOT CREATED!";
			$this->defaults["Pages"]["OrderConfirmationPage"] = $orderConfirmationPage ? $orderConfirmationPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["OrderConfirmationPage"] = true;
		}

		if($accountPage = AccountPage::get()->First()) {
			$this->getPageDefinitions($accountPage);
			$this->definitions["Pages"]["AccountPage"] = "Page where customers can review their account. This page is required.<br />".($accountPage ? "<a href=\"/admin/pages/edit/show/".$accountPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["AccountPage"] = $accountPage ? "view: <a href=\"".$accountPage->Link()."\">".$accountPage->Title."</a><br />".$accountPage->configArray : " NOT CREATED!";
			$this->defaults["Pages"]["AccountPage"] = $accountPage ? $accountPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["AccountPage"] = true;
		}

		if(
			$cartPage = CartPage::get()
				->Filter(array("ClassName" => "CartPage"))
				->First()
		) {
			$this->getPageDefinitions($cartPage);
			$this->definitions["Pages"]["CartPage"] = "Page where customers review their cart while shopping. This page is optional.<br />".($cartPage ? "<a href=\"/admin/pages/edit/show/".$cartPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["CartPage"] = $cartPage ? "view: <a href=\"".$cartPage->Link()."\">".$cartPage->Title."</a>, <a href=\"/admin/pages/edit/show/".$cartPage->ID."/\">edit</a><br />".$cartPage->configArray : " NOT CREATED!";
			$this->defaults["Pages"]["CartPage"] = $cartPage ? $cartPage->defaultsArray : "[add page first to see defaults]";
			$this->defaults["Pages"]["CartPage"] = $cartPage ? $cartPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["CartPage"] = true;
		}
	}

	private function getPageDefinitions(SiteTree $page){
		if($page) {
			$fields = Config::inst()->get($page->ClassName, "db");
			$defaultsArray = $page->stat("defaults", true);
			$configArray = array();
			if($fields) {
				foreach($fields as $fieldKey => $fieldType) {
					$configArray[$fieldKey] = $page->$fieldKey;
					if(!isset($defaultsArray[$fieldKey])) {
						$defaultsArray[$fieldKey] = "[default not set]";
					}
				}
			}
			$page->defaultsArray = $defaultsArray;
			$page->configArray = print_r($configArray, 1);
		}
	}


	function orderSteps(){
		$steps = OrderStep::get();
		if($steps->count()) {
			foreach($steps as $step) {
				$fields = Config::inst()->get($step->ClassName, "db");
				$defaultsArray = $step->stat("defaults", true);
				$configArray = array();
				foreach($fields as $fieldKey => $fieldType) {
					if($fields) {
						$configArray[$fieldKey] = $step->$fieldKey;
						if(!isset($defaultsArray[$fieldKey])) {
							$defaultsArray[$fieldKey] = "[default not set]";
						}
					}
				}
				$ecommerceDBConfig = EcommerceDBConfig::current_ecommerce_db_config();
				$this->definitions["OrderStep"][$step->Code] = $step->Description."<br />see: <a href=\"/admin/shop/OrderStep/EditForm/field/OrderStep/item/".$step->ID."/edit\">Ecommerce Configuration</a>.";
				$this->configs["OrderStep"][$step->Code] = $configArray;
				$this->defaults["OrderStep"][$step->Code] = $defaultsArray;
				$this->databaseValues["OrderStep"][$step->Code] = true;
			}
		}
	}

	function checkoutAndModifierDetails(){
		$checkoutPage = CheckoutPage::get()->First();
		if(!$checkoutPage) {
			$task = new EcommerceTaskDefaultRecords();
			$task->run(null);
			$checkoutPage = CheckoutPage::get()->First();
			if(!$checkoutPage) {
				user_error("There is no checkout page available and it seems impossible to create one.");
			}
		}
		$steps = CheckoutPage_StepDescription::get();
		if($steps->count()) {
			foreach($steps as $key => $step) {
				$stepNumber = $key + 1;
				$fields = Config::inst()->get($step->ClassName, "db");
				$defaultsArray = $step->stat("defaults", true);
				$configArray = array();
				foreach($fields as $fieldKey => $fieldType) {
					if($fields) {
						$configArray[$fieldKey] = $step->$fieldKey;
						if(!isset($defaultsArray[$fieldKey])) {
							$defaultsArray[$fieldKey] = "[default not set]";
						}
					}
				}
				$this->definitions["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = $step->Description."<br />see: <a href=\"/admin/pages/edit/show/".$checkoutPage->ID."/\">checkout page</a>.";
				$this->configs["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = $configArray;
				$this->defaults["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = $defaultsArray;
				$this->databaseValues["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = true;
			}
		}
		$steps = OrderModifier_Descriptor::get();
		if($steps->count()) {
			foreach($steps as $step) {
				$fields = Config::inst()->get($step->ClassName, "db");
				$defaultsArray = $step->stat("defaults", true);
				$configArray = array();
				foreach($fields as $fieldKey => $fieldType) {
					if($fields) {
						$configArray[$fieldKey] = $step->$fieldKey;
						if(!isset($defaultsArray[$fieldKey])) {
							$defaultsArray[$fieldKey] = "[default not set]";
						}
					}
				}
				$this->definitions["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = $step->Description."<br />see: <a href=\"/admin/pages/edit/show/".$checkoutPage->ID."/\">checkout page</a>.";
				$this->configs["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = $configArray;
				$this->defaults["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = $defaultsArray;
				$this->databaseValues["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = true;
			}
		}
	}

	private function getAjaxDefinitions(){
		$definitionsObject = EcommerceConfigDefinitions::create();
		$methodArray = $definitionsObject->getAjaxMethods();
		$requestor = new ArrayData(
			array(
				"ID" => "[ID]",
				"ClassName" => "[CLASSNAME]"
			)
		);
		$obj = EcommerceConfigAjax::get_one($requestor);
		foreach($methodArray as $method => $description) {
			if($method != "setRequestor") {
				if(strpos($method, "lassName")) {
					$selector ="classname";
				}
				else {
					$selector ="id";
				}
				$note = "
					This variable can be used like this: <pre>&lt;div $selector=\"\$AJAXDefinitions.".$method."\"&gt;&lt;/div&gt;</pre>
					<a href=\"/shoppingcart/ajaxtest/?ajax=1\">AJAX</a> will then use this selector to put the following content: ";
				$this->definitions["Templates"]["AJAXDefinitions_$method"] = $note."<br />".$description;
				$this->configs["Templates"]["AJAXDefinitions_$method"] = $obj->$method();
				$this->defaults["Templates"]["AJAXDefinitions_$method"] = "";
				$this->otherConfigs["Templates"]["AJAXDefinitions_$method"] = true;
			}
		}
	}

	/**
	 * check for any additional settings
	 *
	 */
	private function specialCases($className, $key, $actualValue){
		switch($className.".".$key) {
			case "Order_Email.css_file_location":
				if(!file_exists(Director::baseFolder()."/$actualValue")) {
					return "<span style=\"color: red\">ADDITIONAL CHECK: this file ".Director::baseFolder()."/".$actualValue." does not exist! For proper functioning of e-commerce, please make sure to create this file.</span>";
				}
				else {
					return "<span style=\"color: #7da4be\">ADDITIONAL CHECK: file exists.</span>";
				}
				break;
			case "Order.modifiers":
				$classes = ClassInfo::subclassesFor("OrderModifier");
				unset($classes["OrderModifier"]);
				$classesAsString = implode(", <br />", $classes);
				return "<br /><h4>Available Modifiers</h4>$classesAsString";
				break;
			case "OrderStatusLog.available_log_classes_array":
				$classes = ClassInfo::subclassesFor("OrderStatusLog");
				unset($classes["OrderStatusLog"]);
				$classesAsString = implode(", <br />", $classes);
				return "<br /><h4>Available Modifiers</h4>$classesAsString";
				break;
			case "OrderStep.order_steps_to_include":
				$classes = ClassInfo::subclassesFor("OrderStep");
				unset($classes["OrderStep"]);
				$classesAsString = implode("<br /> - ", $classes);
				return "<br /><h4>Available Order Steps</h4> - $classesAsString";
				break;
		}
	}

	private function turnValueIntoHumanReadableValue($actualValue){
		if($actualValue === "") {
			$actualValue = "[FALSE] / [EMPTY STRING] ";
		}
		if($actualValue === null) {
			$actualValue = "[NULL]";
		}
		if($actualValue === "1" || $actualValue === 1) {
			$actualValue = "[TRUE] / 1";
		}
		if($actualValue === "0" || $actualValue === false) {
			$actualValue = "[FALSE] / 0";
		}
		return $actualValue;
	}

	protected function checkGEOIP(){
		if(Config::inst()->get("EcommerceCountry", "visitor_country_provider") == "EcommerceCountry_VisitorCountryProvider" && !class_exists("Geoip")) {
			user_error("
				You need to install Geoip module that has a method Geoip::visitor_country, returning the country code associated with the user's IP address.
				Alternatively you can set the following config EcommerceCountry.visitor_country_provider to something like MyGEOipProvider.
				You then create a class MyGEOipProvider with a method getCountry().",
				E_USER_NOTICE
			);
		}
		elseif(Director::isLive() && !EcommerceCountry::get_country_from_ip()) {
			user_error("
				Please make sure that '".$this->Config()->get("visitor_country_provider")."' (visitor_country_provider) is working on your server (see the GEOIP module for details).",
				E_USER_NOTICE
			);
		}
	}

}

