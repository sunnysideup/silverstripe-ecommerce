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

 **/
class EcommerceDatabaseAdmin extends TaskRunner
{
    //##############################
    // 0. OVERALL CONFIG
    //##############################

    /**
     * List of overall configuration BuildTasks.
     *
     * @var array
     */
    protected $overallconfig = [
        'ecommercetaskcheckconfiguration',
        'ecommercetaskapiandmore',
    ];

    //##############################
    // 1. ECOMMERCE SETUP (DEFAULT RECORDS)
    //##############################

    /**
     * List of setup BuildTasks.
     *
     * @var array
     */
    protected $ecommerceSetup = [
        'ecommercetasksetorderidstartingnumber',
        'ecommercetaskcreatemembergroups',
        'ecommercetaskdefaultrecords',
        'ecommercetaskcountryandregion',
        'EcommerceTaskCountryAndRegiondisallowallcountries',
        'EcommerceTaskCountryAndRegionallowallcountries',
        'ecommercetaskadddefaultproducts',
        'ecommercetasklinkproductwithimages',
    ];

    //##############################
    // 2. DATA REVIEW MAINTENANCE
    //##############################

    /**
     * List of regular maintenance BuildTasks.
     *
     * @var array
     */
    protected $dataReview = [
        'ecommercetaskreviewreports',
        'ecommercetaskreviewsearches',
        'ecommercetaskorderitemspercustomer',
    ];

    //##############################
    // 3. REGULAR MAINTENANCE
    //##############################

    /**
     * List of regular maintenance BuildTasks.
     *
     * @var array
     */
    protected $regularMaintenance = [
        'ecommercetaskcartcleanup',
        'ecommercetaskaddcustomerstocustomergroups',
        'ecommercetaskfixbrokenordersubmissiondata',
        'ecommercetaskcleanupproductfullsitetreesorting',
        'ecommercetaskproductvariationsfixes',
        'ecommercetaskproductimagereset',
        'ecommercetasktrytofinaliseorders',
        'ecommercetaskprocessorderqueue',
        'ecommercetaskarchiveallsubmittedorders',
        'ecommercetasklinkorderaddressesatbothends',
        'EcommerceTaskCleanupProducts',
    ];

    //##############################
    // 4. DEBUG ACTIONS
    //##############################

    /**
     * List of debug actions BuildTasks.
     *
     * @var array
     */
    protected $debugActions = [
        'ecommercetasktemplatetest',
        'ecommercetaskcartmanipulation_current',
        'ecommercetaskcartmanipulation_debug',
        'ecommercetaskbuilding_model',
        'ecommercetaskbuilding_extending',
    ];

    //##############################
    // 5. MIGRATIONS
    //##############################

    /**
     * List of migration BuildTasks.
     *
     * @var array
     */
    protected $migrations = [
        'ecommercetaskmigration',
        'ecommercetaskcheckconfiguration',
        'ecommercetasksetdefaultproductgroupvalues',
    ];

    //##############################
    // 6. CRAZY SHIT
    //##############################

    /**
     * List of crazy shit BuildTasks.
     *
     * @var array
     */
    protected $crazyshit = [
        'ecommercetaskdeleteallorders',
        'ecommercetaskdeleteproducts',
        'ecommercetaskarchiveallorderswithitems',
    ];

    //##############################
    // 7. TESTS
    //##############################

    /**
     * List of tests.
     *
     * @var array
     */
    protected $tests = [
        //'ShoppingCartTest' => 'Shopping Cart'
    ];

    //##############################
    // BASIC FUNCTIONS
    //##############################

    public function index()
    {
        if (Director::is_cli()) {
            echo "SILVERSTRIPE ECOMMERCE TOOLS: Tasks\n--------------------------\n\n";
            foreach ($tasks as $task) {
                echo " * {$task['title']}: sake dev/tasks/" . $task['class'] . "\n";
            }
        } else {
            $renderer = new DebugView_EcommerceDatabaseAdmin();
            $renderer->writeHeader();
            $renderer->writeInfo('SilverStripe Ecommerce Tools', Director::absoluteBaseURL());
            $renderer->writeContent($this);
            $renderer->writeFooter();
        }
    }

    /**
     * standard, required method.
     *
     * @param string $action
     *
     * @return string link for the "Controller"
     */
    public function Link($action = null)
    {
        return Controller::join_links(
            Director::BaseURL(),
            'dev/ecommerce/',
            $action
        );
    }

    /**
     * list of config tasks.
     *
     * @return ArrayList
     */
    public function OverallConfig()
    {
        return $this->createMenuDOSFromArray($this->overallconfig, $type = 'Config');
    }

    /**
     * list of data setup tasks.
     *
     * @return ArrayList
     */
    public function EcommerceSetup()
    {
        return $this->createMenuDOSFromArray($this->ecommerceSetup, $type = 'EcommerceSetup');
    }

    /**
     * regular data cleanup tasks.
     *
     * @return ArrayList
     */
    public function DataReview()
    {
        return $this->createMenuDOSFromArray($this->dataReview, $type = 'DataReview');
    }

    /**
     * regular data cleanup tasks.
     *
     * @return ArrayList
     */
    public function RegularMaintenance()
    {
        return $this->createMenuDOSFromArray($this->regularMaintenance, $type = 'RegularMaintenance');
    }

    /**
     * list of data debug actions.
     *
     * @return ArrayList
     */
    public function DebugActions()
    {
        return $this->createMenuDOSFromArray($this->debugActions, $type = 'DebugActions');
    }

    /**
     * list of migration tasks.
     *
     * @return ArrayList
     */
    public function Migrations()
    {
        return $this->createMenuDOSFromArray($this->migrations, $type = 'Migrations');
    }

    /**
     * list of crazy actions tasks.
     *
     * @return ArrayList
     */
    public function CrazyShit()
    {
        return $this->createMenuDOSFromArray($this->crazyshit, $type = 'CrazyShit');
    }

    public function Tests()
    {
        $arrayList = new ArrayList();
        foreach ($this->tests as $class => $name) {
            $arrayList->push(
                new ArrayData(
                    [
                        'Name' => $name,
                        'Class' => $class,
                    ]
                )
            );
        }

        return $arrayList;
    }

    /**
     * @return array ????
     */
    public function AllTests()
    {
        return implode(',', array_keys($this->tests));
    }

    public function runTask($request)
    {
        $taskName = $request->param('TaskName');
        $renderer = new EcommerceDatabaseAdminDebugView();
        $renderer->writeHeader();
        $renderer->writeInfo('SilverStripe Ecommerce Tools', Director::absoluteBaseURL());
        $renderer->writePreOutcome();
        if (class_exists($taskName) && is_subclass_of($taskName, 'BuildTask')) {
            $title = singleton($taskName)->getTitle();
            if (Director::is_cli()) {
                echo "Running task '${title}'...\n\n";
            } elseif (! Director::is_ajax()) {
                echo "<h1>Running task '${title}'...</h1>\n";
            }

            $task = new $taskName();
            if ($task->isEnabled()) {
                $task->verbose = true;
                $task->run($request);
            } else {
                echo "<p>{$title} is disabled</p>";
            }
        } else {
            echo "Build task '${taskName}' not found.";
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

    //##############################
    // INTERNAL FUNCTIONS
    //##############################

    /**
     * @param array  $buildTasksArray array of build tasks
     * @param string $type
     *
     * @return ArrayList(ArrayData(Link, Title, Description))
     */
    protected function createMenuDOSFromArray(array $buildTasksArray, $type = '')
    {
        $extendedArray = $this->extend('updateEcommerceDevMenu' . $type, $buildTasksArray);
        if ($extendedArray !== null && is_array($extendedArray) && count($extendedArray)) {
            foreach ($extendedArray as $extendedBuildTasks) {
                $buildTasksArray = array_merge($buildTasksArray, $extendedBuildTasks);
            }
        }
        $buildTasksArray = array_unique($buildTasksArray);
        $arrayList = new ArrayList();
        foreach ($buildTasksArray as $buildTask) {
            $obj = new $buildTask();
            $do = new ArrayData(
                [
                    'Link' => $this->Link($buildTask),
                    'Title' => $obj->getTitle(),
                    'Description' => $obj->getDescription(),
                ]
            );
            $arrayList->push($do);
        }

        return $arrayList;
    }

    /**
     * shows a "Task Completed Message" on the screen.
     *
     * @param BuildTask $buildTask
     * @param string    $extraMessage
     */
    protected function displayCompletionMessage(BuildTask $buildTask, $extraMessage = '')
    {
        DB::alteration_message('

            ------------------------------------------------------- <br />
            COMPLETED THE FOLLOWING TASK:<br />
            <strong>' . $buildTask->getTitle() . '</strong><br />
            ' . $buildTask->getDescription() . " <br />
            ------------------------------------------------------- <br />
            ${extraMessage}
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
        echo '</div>';
    }

    public function writeContent(Controller $controller)
    {
        echo $controller->RenderWith($controller->class);
    }
}
