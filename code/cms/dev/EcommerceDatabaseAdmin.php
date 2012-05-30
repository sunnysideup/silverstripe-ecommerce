<?php

/**
 * One stop shop for massaging e-commerce related data
 * AND running tests.
 *
 * You can customise this menu by using the "decorating" this class
 * and adding the method: "updateEcommerceDevMenu".
 *
 * SECTIONS
 *
 * 1. ecommerce setup (default records)
 * 2. maintance
 * 3. debug
 * 4. migration
 * 5. tests
 *
 * @author jeremy, nicolaas
 * @todo: work out a standard "silent" option and a display option the "display" options shows all output when running it from ecommerce/dev/
 * We also have to work out an easy way to extend this.
 */

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

	protected $overallconfig = array(
		"ecommercecheckconfiguration"
	);

	/**
	 * @return DataObjectSet list of migration tasks
	 *
	 */
	function OverallConfig() {
		return $this->createMenuDOSFromArray($this->overallconfig, $type = "Overall Config");
	}

	function ecommercecheckconfiguration($request){
		$this->runTask("EcommerceCheckConfiguration", $request);
	}








	//##############################
	// 1. ECOMMERCE SETUP (DEFAULT RECORDS)
	//##############################

	protected $ecommerceSetup = array(
		"setorderidstartingnumber",
		"createecommercemembergroups",
		"ecommercedefaultrecords",
		"adddefaultecommerceproducts"
	);

	/**
	 * @return DataObjectSet list of data cleanup tasks
	 *
	 */
	function EcommerceSetup() {
		return $this->createMenuDOSFromArray($this->ecommerceSetup, $type = "EcommerceSetup");
	}

	function setorderidstartingnumber($request){
		$this->runTask("SetOrderIDStartingNumber", $request);
	}

	function createecommercemembergroups($request){
		$this->runTask("CreateEcommerceMemberGroups", $request);
	}

	function ecommercedefaultrecords($request){
		$this->runTask("EcommerceDefaultRecords", $request);
	}

	function adddefaultecommerceproducts($request){
		$this->runTask("AddDefaultEcommerceProducts", $request);
	}







	//##############################
	// 2. REGULAR MAINTENANCE
	//##############################

	protected $regularMaintenance = array(
		"clearoldcarts",
		"recalculatethenumberofproductssold",
		"addcustomerstocustomergroups",
		"fixbrokenordersubmissiondata",
		"cleanupproductfullsitetreesorting"
	);

	/**
	 * @return DataObjectSet list of data cleanup tasks
	 *
	 */
	function RegularMaintenance() {
		return $this->createMenuDOSFromArray($this->regularMaintenance, $type = "RegularMaintenance");
	}

	/**
	 * executes build task
	 *
	 */
	function clearoldcarts($request) {
		$this->runTask("ClearOldCarts", $request);
	}

	/**
	 * executes build task
	 *
	 */
	function recalculatethenumberofproductssold($request) {
		$this->runTask("RecalculateTheNumberOfProductsSold", $request);
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








	//##############################
	// 3. DEBUG ACTIONS
	//##############################

	protected $debugActions = array(
		"ecommercetemplatetesttask",
		"cartmanipulation_current",
		"cartmanipulation_debug"
	);

	/**
	 * @return DataObjectSet list of data debug actions
	 *
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

	protected $migrations = array(
		"ecommercemigration",
		"ecommercecheckconfiguration",
		"setdefaultproductgroupvalues",
	);

	/**
	 * @return DataObjectSet list of migration tasks
	 *
	 */
	function Migrations() {
		return $this->createMenuDOSFromArray($this->migrations, $type = "Migrations");
	}

	/**
	 * runs all the available migration tasks.
	 * TO DO:
	 * - how long does this take?
	 * - do we need a special sequence
	 */
	function runallmigrations($request){
		foreach($this->migrations as $buildTask) {
			$buildTask->run($request);
		}
		$this->displayCompletionMessage($buildTask);
	}




	/**
	 * executes build task: FixBrokenOrderSubmissionData
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

	protected $crazyshit = array(
		"deleteallorders"
	);

	/**
	 * @return DataObjectSet list of migration tasks
	 *
	 */
	function CrazyShit() {
		return $this->createMenuDOSFromArray($this->crazyshit, $type = "CrazyShit");
	}


	function deleteallorders($request){
		$this->runTask("DeleteAllOrders", $request);
	}






	//##############################
	// 6. TESTS
	//##############################

	protected $tests = array(
		'ShoppingCartTest' => 'Shopping Cart'
	);

	function Tests(){
		$dos = new DataObjectSet();
		foreach($this->tests as $class => $name){
			$dos->push(new ArrayData(array(
				'Name' => $name,
				'Class' => $class
			)));
		}
		return $dos;
	}

	function AllTests(){
		return implode(',',array_keys($this->tests));
	}


	//##############################
	// INTERNAL FUNCTIONS
	//##############################

	/**
	 * shows a "Task Completed Message" on the screen.
	 */
	public function displayCompletionMessage($buildTask, $extraMessage = '') {
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
	 *@param Array $buildTasks array of build tasks
	 */
	protected function createMenuDOSFromArray($buildTasks, $type = "") {
		$this->extend("updateEcommerceDevMenu".$type, $buildTasks);
		$dos = new DataObjectSet();
		foreach($buildTasks as $buildTask) {
			$obj = new $buildTask();
			$do = new ArrayData(
				array(
					"Link" => $this->Link($buildTask),
					"Title" => $obj->getTitle(),
					"Description" => $obj->getDescription()
				)
			);
			$dos->push($do);
		}
		return $dos;
	}

	protected function runTask($className, $request) {
		$buildTask = new $className();
		$buildTask->run($request);
		$this->displayCompletionMessage($buildTask);
	}


}

