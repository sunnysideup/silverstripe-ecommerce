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

    function run(SS_HTTPRequest $request) {
        DB::query("TRUNCATE TABLE MyUselessTable;");
    }

}

class MyMigration_EXT extends Extension {

    private static $allowed_actions = array(
        "mymigration" => true
    );

    //NOTE THAT updateEcommerceDevMenuConfig adds to Config options
    //but you can als have: updateEcommerceDevMenuDebugActions, or updateEcommerceDevMenuMaintenanceActions
    function updateEcommerceDevMenuConfig($buildTasks){
        $buildTasks[] = "mymigration";
        return $buildTasks;
    }

    function mymigration(SS_HTTPRequest $request){
        $this->owner->runTask("MyMigration", $request);
    }

}


####################### in mysite/_config.php:

Object::add_extension("EcommerceDatabaseAdmin", "MyMigration_EXT");


</code>

 *
 * SECTIONS
 *
 * 0. check settings
 * 1. ecommerce setup (default records)
 * 2. data review
 * 3. regular maintance
 * 4. debug
 * 5. migration
 * 6. reset
 * 7. tests
 *
 * @todo: work out a standard "silent" option and a display option the "display" options shows all output when running it from ecommerce/dev/
 * We also have to work out an easy way to extend this.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceDatabaseAdmin extends TaskRunner
{

    private static $allowed_actions = array(
        "runTask" => true
    );

    //##############################
    // BASIC FUNCTIONS
    //##############################

    public function index()
    {
        if (Director::is_cli()) {
            echo "SILVERSTRIPE ECOMMERCE TOOLS: Tasks\n--------------------------\n\n";
            foreach ($tasks as $task) {
                echo " * $task[title]: sake dev/tasks/" . $task['class'] . "\n";
            }
        } else {
            $renderer = new DebugView_EcommerceDatabaseAdmin();
            $renderer->writeHeader();
            $renderer->writeInfo("SilverStripe Ecommerce Tools", Director::absoluteBaseURL());
            $renderer->writeContent($this);
            $renderer->writeFooter();
        }
    }

    /**
     * standard, required method
     * @param String $action
     * @return String link for the "Controller"
     */
    public function Link($action = null)
    {
        return Controller::join_links(
            Director::BaseURL(),
            'dev/ecommerce/',
            $action
        );
    }





    //##############################
    // 0. OVERALL CONFIG
    //##############################

    /**
     * List of overall configuration BuildTasks
     * @var Array
     */
    protected $overallconfig = array(
        "ecommercetaskcheckconfiguration",
        "ecommercetaskapiandmore"
    );

    /**
     * list of config tasks
     * @return ArrayList
     */
    public function OverallConfig()
    {
        return $this->createMenuDOSFromArray($this->overallconfig, $type = "Config");
    }







    //##############################
    // 1. ECOMMERCE SETUP (DEFAULT RECORDS)
    //##############################

    /**
     * List of setup BuildTasks
     * @var Array
     */
    protected $ecommerceSetup = array(
        "ecommercetasksetorderidstartingnumber",
        "ecommercetaskcreatemembergroups",
        "ecommercetaskdefaultrecords",
        "ecommercetaskcountryandregion",
        "ecommercetaskcountryandregion_disallowallcountries",
        "ecommercetaskcountryandregion_allowallcountries",
        "ecommercetaskadddefaultproducts",
        "ecommercetasklinkproductwithimages"
    );

    /**
     * list of data setup tasks
     * @return ArrayList
     */
    public function EcommerceSetup()
    {
        return $this->createMenuDOSFromArray($this->ecommerceSetup, $type = "EcommerceSetup");
    }




    //##############################
    // 2. DATA REVIEW MAINTENANCE
    //##############################

    /**
     * List of regular maintenance BuildTasks
     * @var Array
     */
    protected $dataReview = array(
        "ecommercetaskreviewreports",
        "ecommercetaskreviewsearches",
        "ecommercetaskorderitemspercustomer",
        "ecommercetaskexportallorderitems"
    );

    /**
     * regular data cleanup tasks
     * @return ArrayList
     *
     */
    public function DataReview()
    {
        return $this->createMenuDOSFromArray($this->dataReview, $type = "DataReview");
    }


    //##############################
    // 3. REGULAR MAINTENANCE
    //##############################

    /**
     * List of regular maintenance BuildTasks
     * @var Array
     */
    protected $regularMaintenance = array(
        "ecommercetaskcartcleanup",
        "ecommercetaskaddcustomerstocustomergroups",
        "ecommercetaskfixbrokenordersubmissiondata",
        "ecommercetaskcleanupproductfullsitetreesorting",
        "ecommercetaskproductvariationsfixes",
        "ecommercetaskproductimagereset",
        "ecommercetasktrytofinaliseorders",
        "ecommercetaskarchiveallsubmittedorders",
        "ecommercetasklinkorderaddressesatbothends",
        "EcommerceTaskCleanupProducts"
    );

    /**
     * regular data cleanup tasks
     * @return ArrayList
     *
     */
    public function RegularMaintenance()
    {
        return $this->createMenuDOSFromArray($this->regularMaintenance, $type = "RegularMaintenance");
    }







    //##############################
    // 4. DEBUG ACTIONS
    //##############################

    /**
     * List of debug actions BuildTasks
     * @var Array
     */
    protected $debugActions = array(
        "ecommercetasktemplatetest",
        "ecommercetaskcartmanipulation_current",
        "ecommercetaskcartmanipulation_debug",
        "ecommercetaskbuilding_model",
        "ecommercetaskbuilding_extending",
    );

    /**
     * list of data debug actions
     * @return ArrayList
     */
    public function DebugActions()
    {
        return $this->createMenuDOSFromArray($this->debugActions, $type = "DebugActions");
    }







    //##############################
    // 5. MIGRATIONS
    //##############################

    /**
     * List of migration BuildTasks
     * @var Array
     */
    protected $migrations = array(
        "ecommercetaskmigration",
        "ecommercetaskcheckconfiguration",
        "ecommercetasksetdefaultproductgroupvalues",
    );

    /**
     * list of migration tasks
     * @return ArrayList
     */
    public function Migrations()
    {
        return $this->createMenuDOSFromArray($this->migrations, $type = "Migrations");
    }



    //##############################
    // 6. CRAZY SHIT
    //##############################


    /**
     * List of crazy shit BuildTasks
     * @var Array
     */
    protected $crazyshit = array(
        "ecommercetaskdeleteallorders",
        "ecommercetaskdeleteproducts",
        "ecommercetaskarchiveallorderswithitems",
    );

    /**
     * list of crazy actions tasks
     * @return ArrayList
     */
    public function CrazyShit()
    {
        return $this->createMenuDOSFromArray($this->crazyshit, $type = "CrazyShit");
    }






    //##############################
    // 7. TESTS
    //##############################

    /**
     * List of tests
     * @var Array
     */
    protected $tests = array(
        //'ShoppingCartTest' => 'Shopping Cart'
    );

    public function Tests()
    {
        $arrayList = new ArrayList();
        foreach ($this->tests as $class => $name) {
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
    public function AllTests()
    {
        return implode(',', array_keys($this->tests));
    }


    //##############################
    // INTERNAL FUNCTIONS
    //##############################


    /**
     *
     * @param Array $buildTasksArray array of build tasks
     * @param String $type
     *
     * @return ArrayList(ArrayData(Link, Title, Description))
     */
    protected function createMenuDOSFromArray(array $buildTasksArray, $type = "")
    {
        $extendedBuildTasksArray = $this->extend("updateEcommerceDevMenu".$type, $buildTasksArray);
        if ($extendedBuildTasksArray !== null && is_array($extendedBuildTasksArray) && count($extendedBuildTasksArray)) {
            foreach ($extendedBuildTasksArray as $extendedBuildTasks) {
                $buildTasksArray += $extendedBuildTasks;
            }
        }
        $arrayList = new ArrayList();
        foreach ($buildTasksArray as $buildTask) {
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

    public function runTask($request)
    {
        $taskName = $request->param('TaskName');
        $renderer = new DebugView_EcommerceDatabaseAdmin();
        $renderer->writeHeader();
        $renderer->writeInfo("SilverStripe Ecommerce Tools", Director::absoluteBaseURL());
        $renderer->writePreOutcome();
        if (class_exists($taskName) && is_subclass_of($taskName, 'BuildTask')) {
            $title = singleton($taskName)->getTitle();
            if (Director::is_cli()) {
                echo "Running task '$title'...\n\n";
            } elseif (!Director::is_ajax()) {
                echo "<h1>Running task '$title'...</h1>\n";
            }

            $task = new $taskName();
            if ($task->isEnabled()) {
                $task->verbose = true;
                $task->run($request);
            } else {
                echo "<p>{$title} is disabled</p>";
            }
        } else {
            echo "Build task '$taskName' not found.";
            if (class_exists($taskName)) {
                echo "  It isn't a subclass of BuildTask.";
            }
            echo "\n";
        }
        $this->displayCompletionMessage($task);
        $renderer->writePostOutcome();
        $renderer->writeContent($this);
        $renderer->writeFooter();
    }

    /**
     * shows a "Task Completed Message" on the screen.
     * @param BuildTask $buildTask
     * @param String $extraMessage
     */
    protected function displayCompletionMessage(BuildTask $buildTask, $extraMessage = '')
    {
        DB::alteration_message("

			------------------------------------------------------- <br />
			COMPLETED THE FOLLOWING TASK:<br />
			<strong>".$buildTask->getTitle()."</strong><br />
			".$buildTask->getDescription()." <br />
			------------------------------------------------------- <br />
			$extraMessage
		");
    }
}


class DebugView_EcommerceDatabaseAdmin extends DebugView
{


    public function writePreOutcome()
    {
        echo "<div id='TaskHolder' style=\"background-color: #e8e8e8; border-radius: 15px; margin: 20px; padding: 20px\">";
    }

    public function writePostOutcome()
    {
        echo "</div>";
    }

    public function writeContent(Controller $controller)
    {
        echo $controller->RenderWith($controller->class);
    }
}
