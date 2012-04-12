<?php

/**
 * This class reviews all of the static configurations in e-commerce for review
 * (a) which configs are set, but not required
 * (b) which configs are required, but not set
 * (c) review of set configs
 *
 */

class EcommerceCheckConfiguration extends BuildTask{

	/**
	 * Default Location for Configuration File
	 * @var String
	 */
	protected $defaultLocation = "ecommerce/_config/ecommerce.yaml";

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
	 * Array of configs - set like this:
	 * ClassName
	 * 		VariableName: VariableValue
	 * @var Array
	 */
	protected $configs = array();

	/**
	 * Array of definitions - set like this:
	 * ClassName
	 * 		VariableName: VariableValue
	 * @var Array
	 */
	protected $definitions = array();

	/**
	 * Array of defaults - set like this:
	 * ClassName
	 * 		VariableName: VariableValue
	 * @var Array
	 */
	protected $defaults = array();

	/**
	 * Standard (required) SS method, runs buildtask
	 */
	function run($request){
		$definitionsObject = new EcommerceConfigDefinitions();
		$this->definitions = $definitionsObject->Definitions();
		$configsObject = new EcommerceConfig();
		$this->configs = $configsObject->getCompleteDataSet();
		$this->defaults = $this->getDefaultValues();
		if($this->definitions) {
			if($this->configs) {
				if($this->defaults) {
					$this->checkFiles();
					$this->classesThatDoNotExist();
					$this->definitionsNotSet();
					$this->configsNotSet();
					$this->addSiteConfigToConfigs();
					$this->addOtherValuesToConfigs();
					$this->definedConfigs();
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
			DB::alteration_message("ERROR: could not find any defitions", "deleted");
		}

	}

	/**
	 * Check what files is being used
	 */
	protected function checkFiles(){
		$configsObject = new EcommerceConfig();
		DB::alteration_message("<h2>Files Used</h2>");
		$files = implode(", ", $configsObject->fileLocations());
		global $project;
		$baseFolder = Director::baseFolder();
		$projectFolder = $project."/_config";
		$baseAndProjectFolder = $baseFolder."/".$projectFolder;
		$file = "ecommerce.yaml";
		$projectFolderAndFile = $projectFolder."/".$file;
		$fullFilePath = $baseFolder."/".$projectFolderAndFile;
		$defaultFileFullPath = Director::baseFolder()."/".$this->defaultLocation;
		if(!file_exists($baseAndProjectFolder)) {
			mkdir($baseAndProjectFolder);
		}
		if(!file_exists($fullFilePath)) {
			copy($defaultFileFullPath, $fullFilePath);
			DB::alteration_message("We have created a new configuration file for you.", "created");
		}
		if($files == $this->defaultLocation) {
			if(file_exists($fullFilePath)) {
				DB::alteration_message("A customisable configuration file exists here: $projectFolderAndFile, you should add the following to your _config.php file:
				<pre>EcommerceConfig::set_folder_and_file_location(\"$projectFolderAndFile\");</pre>
				 ", "created");
			}
		}
		echo "Current Files used: ".$files;
	}

	/**
	 * Work out items set in the configuration but not set in the config file.
	 */
	protected function definitionsNotSet(){
		DB::alteration_message("<h2>Set in configs but not defined</h2>");
		$allOK = true;
		foreach($this->configs as $className => $setting) {
			if(!isset($this->definitions[$className])) {
				$allOK = false;
				DB::alteration_message("$className", "Edited");
			}
			else {
				$classConfigs = $this->configs[$className];
				foreach($classConfigs as $key => $classConfig) {
					if(!isset($this->definitions[$className][$key])) {
						$allOK = false;
						DB::alteration_message("$className.$key", "Edited");
					}
				}
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
	}


	/**
	 * Work out items set in the configuration but not set in the config file.
	 */
	protected function classesThatDoNotExist(){
		DB::alteration_message("<h2>Classes that do not exist</h2>");
		$allOK = true;
		foreach($this->configs as $className => $setting) {
			if(!class_exists($className)) {
				$allOK = false;
				DB::alteration_message("$className", "Edited");
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
	}


	/**
	 * Work out items set in the definitions but not set in the config file.
	 */
	protected function configsNotSet(){
		$allOK = true;
		DB::alteration_message("<h2>Defined but not set in configs</h2>");
		foreach($this->definitions as $className => $setting) {
			if(!isset($this->configs[$className])) {
				DB::alteration_message("$className", "deleted");
				$allOK = false;
			}
			else {
				$classConfigs = $this->definitions[$className];
				foreach($classConfigs as $key => $classConfig) {
					if(!isset($this->configs[$className][$key])) {
						DB::alteration_message("$className.$key", "deleted");
						$allOK = false;
					}
				}
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
	}

	/**
	 * Work out items set in the definitions but not set in the config file.
	 */
	protected function definedConfigs(){
		$htmlHeader = "
		<style>
			th[scope='col'] {text-align: left; border-bottom: 3px solid blue;padding-top: 40px;}
			td {vertical-align: top; border-left: 1px solid blue; border-bottom: 1px solid blue;}
			td span {color: grey; font-size: 0.8em;}
			td span {color: grey; font-size: 0.8em; display: block}
			.sameConfig {color: #333;}
			.newConfig{color: green;}
			#TOC {
				-moz-column-count: 3;
				-moz-column-gap: 20px;
				-webkit-column-count: 3;
				-webkit-column-gap: 20px;
				column-count: 3;
				column-gap: 20px;
			}
			a.backToTop {display: block; float: right; font-size: 0.8em; }
		</style>
		<h2>Configuration Report</h2>";
		$htmlTable = "
		<table summary=\"list of configs\">
		";
		$oldClassName = "";
		$htmlTOC = "<ol id=\"TOC\">";
		$count = 0;
		foreach($this->configs as $className => $settings) {
			$count++;
			$htmlTOC .= "<li><a href=\"#$className\">$className</a></li>";
			if($className != $oldClassName) {
				$htmlTable .= "<tr id=\"$className\"><th colspan=\"2\" scope=\"col\">$count. $className <a class=\"backToTop\" href=\"#TOC\">top</a></th></tr>";
				$oldClassName = $className;
			}

			foreach($settings as $key => $classConfig) {
				$defaultValue = print_r($this->configs[$className][$key], 1);
				$actualValue = print_r($this->configs[$className][$key], 1);
				if($actualValue == $defaultValue) {
					$class = "sameConfig";
				}
				else {
					$class = "newConfig";
				}
				if($actualValue === false || $actualValue === "") {
					$actualValue = "[FALSE] / [EMPTY STRING]";
				}
				if($actualValue === null) {
					$actualValue = "[NULL]";
				}
				if($actualValue === "1") {
					$actualValue = "[TRUE]";
				}
				$description = $this->definitions[$className][$key];
				$htmlTable .= "<tr>
			<td>
				$key
				<span>$description</span>
			</td>
			<td class=\"$class\"><pre>$actualValue</pre></td>
		</tr>";
			}
		}
		$htmlEnd = "
		</table>
		<h2>--- THE END ---</h2>
		";
		$htmlTOC .= "</ol>";
		echo $htmlHeader.$htmlTOC.$htmlTable.$htmlEnd;
	}

	protected function getDefaultValues(){
		require_once 'thirdparty/spyc/spyc.php';
		$fixtureFolderAndFile = Director::baseFolder()."/".$this->defaultLocation;
		$parser = new Spyc();
		return $parser->loadFile($fixtureFolderAndFile);
	}

	protected function addSiteConfigToConfigs(){
		$siteConfig = SiteConfig::current_site_config();
		$fields = $siteConfig->fieldLabels();
		foreach($fields as $field => $description) {
			$this->definitions["SiteConfig"][$field] = "$description. <br />THIS IS SET IN THE <a href=\"/admin/root\">SiteConfig</a>";
			$this->configs["SiteConfig"][$field] = $siteConfig->$field;
			$imageField = $field."ID";
			if(isset($siteConfig->$imageField)) {
				if($image = $siteConfig->$field()) {
					if($image->exists() && $image instanceOF Image) {
						$this->configs["SiteConfig"][$field] = "[Image]  --- <img src=\"".$image->Link()."\" />";
					}
				}
			}
		}
	}


	protected function addOtherValuesToConfigs(){
		$this->definitions["Payment"]["site_currency"] = "Default currency for the site. <br />SET USING Payment::set_site_currency(\"NZD\")";
		$this->configs["Payment"]["site_currency"] = Payment::site_currency()." ";

		$this->definitions["Geoip"]["default_country_code"] = "Default currency for the site. <br />SET USING Geoip::\$default_country_code";
		$this->configs["Geoip"]["default_country_code"] = Geoip::$default_country_code;

		$this->definitions["Email"]["admin_email_address"] = "Default administrator email. SET USING Email::\$admin_email_address = \"bla@ta.com\"";
		$this->configs["Email"]["admin_email_address"] = Email::$admin_email_address;
	}

}
