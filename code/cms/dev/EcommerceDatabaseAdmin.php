<?php


/**
 * One stop shop for massaging e-commerce related data
 * AND running tests.
 *
 * You can customise this menu by "decorating" this class
 * and adding the method: "updateEcommerceDevMenu".
 *
 * Here is an example:

<code php>
<?php

####################### in mysite/code/tasks/MyMigration.php

class MyMigration extends BuildTask {

	protected $title = "Mysite Database Fixes";

	protected $description = "General DB fixes";

	function run($request) {
		DB::query("TRUNCATE TABLE MyUselessTable;");
	}

}

class MyMigration_EXT extends Extension {

	static $allowed_actions = array(
		"mymigration" => true
	);

	//NOTE THAT updateEcommerceDevMenuConfig adds to Config options
	//but you can als have: updateEcommerceDevMenuDebugActions
	function updateEcommerceDevMenuConfig($buildTasks){
		$buildTasks[] = "mymigration";
		return $buildTasks;
	}

	function mymigration($request){
		$this->owner->runTask("MyMigration", $request);
	}

}


####################### in mysite/_config.php:

Object::add_extension("EcommerceDatabaseAdmin", "MyMigration_EXT");


</code>

 *
 * SECTIONS
 *
 * 1. ecommerce setup (default records)
 * 2. maintance
 * 3. debug
 * 4. migration
 * 5. tests
 * @todo: work out a standard "silent" option and a display option the "display" options shows all output when running it from ecommerce/dev/
 * We also have to work out an easy way to extend this.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceDatabaseAdmin extends Controller{

	//##############################
	// BASIC FUNCTIONS
	//##############################

	static $url_handlers = array(
		//'' => 'browse',
	);

	/**
	 * standard Silverstripe method - required
	 *
	 */
	function init() {
		parent::init();
		// We allow access to this controller regardless of live-status or ADMIN permission only
		// or if on CLI.
		// Access to this controller is always allowed in "dev-mode", or if the user is ADMIN.
		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		$canAccess = (
			Director::isDev()
			// We need to ensure that DevelopmentAdminTest can simulate permission failures when running
			// "dev/tests" from CLI.
			|| (Director::is_cli() && !$isRunningTests)
			|| Permission::check("ADMIN")
			|| Permission::check(EcommerceConfig::get("EcommerceRole", "admin_group_code"))
		);
		if(!$canAccess) {
			return Security::permissionFailure($this,
				"The e-commerce development control panel is secured and you need administrator rights to access it. " .
				"Enter your credentials below and we will send you right along.");
		}
	}

	/**
	 * standard, required method
	 * @return String link for the "Controller"
	 */
	public function Link($action = null) {
		$action = ($action) ? $action : "";
		return Controller::join_links(Director::absoluteBaseURL(), 'dev/ecommerce/'.$action);
	}





	//##############################
	// 0. OVERALL CONFIG
	//##############################

	/**
	 * List of overall configuration BuildTasks
	 * @var Array
	 */
	protected $overallconfig = array(
		"ecommercecheckconfiguration"
	);

	/**
	 * list of config tasks
	 * @return ArrayList
	 */
	function OverallConfig() {
		return $this->createMenuDOSFromArray($this->overallconfig, $type = "Config");
	}

	/**
	 * execute the task
	 * @param HTTPRequest $request
	 */
	function ecommercecheckconfiguration($request){
		$this->runTask("EcommerceCheckConfiguration", $request);
	}








	//##############################
	// 1. ECOMMERCE SETUP (DEFAULT RECORDS)
	//##############################

	/**
	 * List of setup BuildTasks
	 * @var Array
	 */
	protected $ecommerceSetup = array(
		"setorderidstartingnumber",
		"createecommercemembergroups",
		"ecommercedefaultrecords",
		"ecommercecountryandregiontasks",
		"adddefaultecommerceproducts",
		"ecommercetasklinkproductwithimages"
	);

	/**
	 * list of data setup tasks
	 * @return ArrayList
	 */
	function EcommerceSetup() {
		return $this->createMenuDOSFromArray($this->ecommerceSetup, $type = "EcommerceSetup");
	}

	/**
	 * execute the task
	 * @param HTTPRequest $request
	 */
	function setorderidstartingnumber($request){
		$this->runTask("SetOrderIDStartingNumber", $request);
	}

	/**
	 * execute the task
	 * @param HTTPRequest $request
	 */
	function createecommercemembergroups($request){
		$this->runTask("CreateEcommerceMemberGroups", $request);
	}

	/**
	 * execute the task
	 * @param HTTPRequest $request
	 */
	function ecommercedefaultrecords($request){
		$this->runTask("EcommerceDefaultRecords", $request);
	}

	/**
	 * execute the task
	 * @param HTTPRequest $request
	 */
	function adddefaultecommerceproducts($request){
		$this->runTask("AddDefaultEcommerceProducts", $request);
	}

	/**
	 * execute the task
	 * @param HTTPRequest $request
	 */
	function ecommercecountryandregiontasks($request){
		$this->runTask("EcommerceCountryAndRegionTasks", $request);
	}

	/**
	 * execute the task
	 * @param HTTPRequest $request
	 */
	function ecommercetasklinkproductwithimages($request){
		$this->runTask("EcommerceTaskLinkProductWithImages", $request);
	}







	//##############################
	// 2. REGULAR MAINTENANCE
	//##############################

	/**
	 * List of regular maintenance BuildTasks
	 * @var Array
	 */
	protected $regularMaintenance = array(
		"cartcleanuptask",
		"addcustomerstocustomergroups",
		"fixbrokenordersubmissiondata",
		"cleanupproductfullsitetreesorting",
		"ecommerceproductvariationsfixestask",
		"ecommerceproductimagereset",
		"ecommercetrytofinaliseorderstask",
		"ecommercetaskarchiveallsubmittedorders"
	);

	/**
	 * regular data cleanup tasks
	 * @return ArrayList
	 *
	 */
	function RegularMaintenance() {
		return $this->createMenuDOSFromArray($this->regularMaintenance, $type = "RegularMaintenance");
	}

	/**
	 * executes build task
	 *
	 */
	function cartcleanuptask($request) {
		$this->runTask("CartCleanupTask", $request);
	}

	/**
	 * executes build task: AddCustomersToCustomerGroups
	 *
	 */
	function addcustomerstocustomergroups($request) {
		$this->runTask("AddCustomersToCustomerGroups", $request);
	}

	/**
	 * executes build task: FixBrokenOrderSubmissionData
	 *
	 */
	function fixbrokenordersubmissiondata($request) {
		$this->runTask("FixBrokenOrderSubmissionData", $request);
	}

	/**
	 * executes build task: CleanupProductFullSiteTreeSorting
	 *
	 */
	function cleanupproductfullsitetreesorting($request) {
		$this->runTask("CleanupProductFullSiteTreeSorting", $request);
	}

	/**
	 * executes build task: EcommerceProductVariationsFixesTask
	 *
	 */
	function ecommerceproductvariationsfixestask($request) {
		$this->runTask("EcommerceProductVariationsFixesTask", $request);
	}

	/**
	 * executes build task: EcommerceProductImageReset
	 *
	 */
	function ecommerceproductimagereset($request) {
		$this->runTask("EcommerceProductImageReset", $request);
	}

	/**
	 * executes build task: EcommerceTryToFinaliseOrdersTask
	 *
	 */
	function ecommercetrytofinaliseorderstask($request) {
		$this->runTask("EcommerceTryToFinaliseOrdersTask", $request);
	}

	/**
	 * executes build task: EcommerceTaskArchiveAllSubmittedOrders
	 *
	 */
	function ecommercetaskarchiveallsubmittedorders($request) {
		$this->runTask("EcommerceTaskArchiveAllSubmittedOrders", $request);
	}








	//##############################
	// 3. DEBUG ACTIONS
	//##############################

	/**
	 * List of debug actions BuildTasks
	 * @var Array
	 */
	protected $debugActions = array(
		"ecommercetemplatetesttask",
		"cartmanipulation_current",
		"cartmanipulation_debug"
	);

	/**
	 * list of data debug actions
	 * @return ArrayList
	 */
	function DebugActions() {
		return $this->createMenuDOSFromArray($this->debugActions, $type = "DebugActions");
	}


	function ecommercetemplatetesttask($request){
		$this->runTask("EcommerceTemplateTestTask", $request);
	}

	function cartmanipulation_current($request){
		$this->runTask("CartManipulation_Current", $request);
	}

	function CartManipulation_Debug($request){
		$this->runTask("CartManipulation_Debug", $request);
	}






	//##############################
	// 4. MIGRATIONS
	//##############################

	/**
	 * List of migration BuildTasks
	 * @var Array
	 */
	protected $migrations = array(
		"ecommercemigration",
		"ecommercecheckconfiguration",
		"setdefaultproductgroupvalues",
	);

	/**
	 * list of migration tasks
	 * @return ArrayList
	 */
	function Migrations() {
		return $this->createMenuDOSFromArray($this->migrations, $type = "Migrations");
	}


	/**
	 * executes build task: EcommerceMigration
	 *
	 */
	function ecommercemigration($request) {
		$this->runTask("EcommerceMigration", $request);
	}



	/**
	 * executes build task: SetDefaultProductGroupValues
	 *
	 */
	function setdefaultproductgroupvalues($request) {
		$this->runTask("SetDefaultProductGroupValues", $request);
	}





	//##############################
	// 5. CRAZY SHIT
	//##############################


	/**
	 * List of crazy shit BuildTasks
	 * @var Array
	 */
	protected $crazyshit = array(
		"deleteallorders",
		"deleteecommerceproductstask"
	);

	/**
	 * list of crazy actions tasks
	 * @return ArrayList
	 */
	function CrazyShit() {
		return $this->createMenuDOSFromArray($this->crazyshit, $type = "CrazyShit");
	}


	function deleteallorders($request){
		$this->runTask("DeleteAllOrders", $request);
	}

	function deleteecommerceproductstask($request){
		$this->runTask("DeleteEcommerceProductsTask", $request);
	}






	//##############################
	// 6. TESTS
	//##############################

	/**
	 * List of tests
	 * @var Array
	 */
	protected $tests = array(
		'ShoppingCartTest' => 'Shopping Cart'
	);

	function Tests(){
		$arrayList = new ArrayList();
		foreach($this->tests as $class => $name){
			$arrayList->push(
				new ArrayData(
					array(
						'Name' => $name,
						'Class' => $class
					)
				)
			);
		}
		return $arrayList;
	}

	/**
	 *
	 * @return Array ????
	 */
	function AllTests(){
		return implode(',',array_keys($this->tests));
	}


	//##############################
	// INTERNAL FUNCTIONS
	//##############################

	/**
	 * shows a "Task Completed Message" on the screen.
	 * @param BuildTask $buildTask
	 * @param String $extraMessage
	 */
	public function displayCompletionMessage(BuildTask $buildTask, $extraMessage = '') {
		DB::alteration_message("
			------------------------------------------------------- <br />
			<strong>".$buildTask->getTitle()."</strong><br />
			".$buildTask->getDescription()." <br />
			TASK COMPLETED.<br />
			------------------------------------------------------- <br />
			$extraMessage
		");
	}

	/**
	 *
	 * @param Array $buildTasks array of build tasks
	 * @param String $type
	 * @return ArrayList(ArrayData(Link, Title, Description))
	 */
	protected function createMenuDOSFromArray($buildTasksArray, $type = "") {
		$extendedBuildTasksArray = $this->extend("updateEcommerceDevMenu".$type, $buildTasksArray);
		if(is_array($extendedBuildTasksArray)) {
			foreach($extendedBuildTasksArray as $extendedBuildTasks) {
				$buildTasksArray += $extendedBuildTasks;
			}
		}
		$arrayList = new ArrayList();
		foreach($buildTasksArray as $buildTask) {
			$obj = new $buildTask();
			$do = new ArrayData(
				array(
					"Link" => $this->Link($buildTask),
					"Title" => $obj->getTitle(),
					"Description" => $obj->getDescription()
				)
			);
			$arrayList->push($do);
		}
		return $arrayList;
	}

	/**
	 *
	 * @param String $className
	 * @param HTTPRequest request
	 */
	public function runTask($className, $request) {
		$buildTask = new $className();
		$buildTask->verbose = true;
		$buildTask->run($request);
		$this->displayCompletionMessage($buildTask);
	}


}

