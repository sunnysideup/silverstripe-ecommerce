<?php

namespace Sunnysideup\Ecommerce\Cms\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\TaskRunner;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\View\ArrayData;

/**
 * Class \Sunnysideup\Ecommerce\Cms\Dev\EcommerceDatabaseAdmin
 *
 */
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
        'Sunnysideup\Ecommerce\Tasks\EcommerceTaskCheckConfiguration',
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
        'ecommercetaskcleanupproducts',
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
        } else {
            $renderer = new EcommerceDatabaseAdminDebugView();
            $renderer->renderHeader();
            $renderer->renderInfo('SilverStripe Ecommerce Tools', Director::absoluteBaseURL());
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
     * @return \SilverStripe\ORM\ArrayList
     */
    public function OverallConfig()
    {
        return $this->createMenuDOSFromArray($this->overallconfig, Config::class);
    }

    /**
     * list of data setup tasks.
     *
     * @return \SilverStripe\ORM\ArrayList
     */
    public function EcommerceSetup()
    {
        return $this->createMenuDOSFromArray($this->ecommerceSetup, 'EcommerceSetup');
    }

    /**
     * regular data cleanup tasks.
     *
     * @return \SilverStripe\ORM\ArrayList
     */
    public function DataReview()
    {
        return $this->createMenuDOSFromArray($this->dataReview, 'DataReview');
    }

    /**
     * regular data cleanup tasks.
     *
     * @return \SilverStripe\ORM\ArrayList
     */
    public function RegularMaintenance()
    {
        return $this->createMenuDOSFromArray($this->regularMaintenance, 'RegularMaintenance');
    }

    /**
     * list of data debug actions.
     *
     * @return \SilverStripe\ORM\ArrayList
     */
    public function DebugActions()
    {
        return $this->createMenuDOSFromArray($this->debugActions, $type = 'DebugActions');
    }

    /**
     * list of migration tasks.
     *
     * @return \SilverStripe\ORM\ArrayList
     */
    public function Migrations()
    {
        return $this->createMenuDOSFromArray($this->migrations, $type = 'Migrations');
    }

    /**
     * list of crazy actions tasks.
     *
     * @return \SilverStripe\ORM\ArrayList
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
        $task = null;
        $taskName = $request->param('TaskName');
        $renderer = new EcommerceDatabaseAdminDebugView();
        $renderer->renderHeader();
        $renderer->renderInfo('SilverStripe Ecommerce Tools', Director::absoluteBaseURL());
        $renderer->writePreOutcome();
        if (class_exists($taskName) && is_subclass_of($taskName, BuildTask::class)) {
            $title = singleton($taskName)->getTitle();
            if (Director::is_cli()) {
                echo "Running task '{$title}'...\n\n";
            } elseif (! Director::is_ajax()) {
                echo "<h1>Running task '{$title}'...</h1>\n";
            }

            $task = new $taskName();
            if ($task->isEnabled()) {
                $task->verbose = true;
                $task->run($request);
            } else {
                echo "<p>{$title} is disabled</p>";
            }
        } else {
            echo "Build task '{$taskName}' not found.";
            if (class_exists($taskName)) {
                echo "  It isn't a subclass of BuildTask.";
            }
            echo "\n";
        }
        $this->displayCompletionMessage($task);
        $renderer->writePostOutcome();
        $renderer->writeContent($this);
        $renderer->writeFooter();

        return [];
    }

    //##############################
    // INTERNAL FUNCTIONS
    //##############################

    /**
     * @param array  $buildTasksArray array of build tasks
     * @param string $type
     *
     * @return \SilverStripe\ORM\ArrayList(ArrayData(Link, Title, Description))
     */
    protected function createMenuDOSFromArray(array $buildTasksArray, $type = '')
    {
        $extendedArray = $this->extend('updateEcommerceDevMenu' . $type, $buildTasksArray);
        if (null !== $extendedArray && is_array($extendedArray) && count($extendedArray)) {
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
     * @param string $extraMessage
     */
    protected function displayCompletionMessage(BuildTask $buildTask, $extraMessage = '')
    {
        DB::alteration_message('

            ------------------------------------------------------- <br />
            COMPLETED THE FOLLOWING TASK:<br />
            <strong>' . $buildTask->getTitle() . '</strong><br />
            ' . $buildTask->getDescription() . " <br />
            ------------------------------------------------------- <br />
            {$extraMessage}
        ");
    }
}
