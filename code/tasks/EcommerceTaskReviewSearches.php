<?php

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 **/
class EcommerceTaskReviewSearches extends BuildTask
{
    /**
     * number of days shown.
     *
     * @int
     */
    protected $defaultDays = 100;

    /**
     * minimum number of searches for
     * a particular keyword in order to show it at all.
     *
     * @int
     */
    protected $defaultMinimum = 5;

    /**
     * show up to XXX days ago.
     *
     * @int
     */
    protected $endingDaysBack = 0;

    /**
     * Standard (required) SS variable for BuildTasks.
     *
     * @var string
     */
    protected $title = 'Search Statistics';

    /**
     * Standard (required) SS variable for BuildTasks.
     *
     * @var string
     */
    protected $description = '
        What did people search for on the website, you can use the days, min and ago GET variables to query different sets.';

    public function run($request)
    {
        $days = intval($request->getVar('days') - 0);
        if (!$days) {
            $days = $this->defaultDays;
        }
        $countMin = intval($request->getVar('min') - 0);
        if (!$countMin) {
            $countMin = $this->defaultMinimum;
        }
        $endingDaysBack = intval($request->getVar('ago') - 0);
        if (!$endingDaysBack) {
            $endingDaysBack = $this->endingDaysBack;
        }
        $field = EcommerceSearchHistoryFormField::create('stats', $this->title)
            ->setNumberOfDays($days)
            ->setMinimumCount($countMin)
            ->setEndingDaysBack($endingDaysBack);
        echo $field->forTemplate();
        $arrayNumberOfDays = array(30, 365);

        $fields = FieldList::create(
            HeaderField::create(
                'ShowResultsFor',
                'Show results for ...'
            ),
            NumericField::create(
                'days',
                'Number of days',
                isset($_GET['days']) ? $_GET['days'] : 30
            ),
            NumericField::create(
                'ago',
                'Starting how many days go',
                isset($_GET['ago']) ? $_GET['ago'] : 30
            ),
            NumericField::create(
                'min',
                'Count treshold',
                isset($_GET['min']) ? $_GET['min'] : 5
            )
        );
        $actions = FieldList::create( FormAction::create("run")->setTitle("show results"));
        $form = Form::create($this, "SearchFields", $fields, $actions, null );
        $form->setAttribute('method','get');
        $form->setAttribute('action',$this->Link());
        echo $form->forTemplate();
    }

    function Link($action = null)
    {
        return '/dev/tasks/EcommerceTaskReviewSearches/';
    }
}
