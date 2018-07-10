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
                isset($_GET['days']) ? $_GET['days'] : $this->defaultDays
            )->setRightTitle('For example, enter 10 to get results from a 10 day period.'),
            NumericField::create(
                'ago',
                'Up to how many days go',
                isset($_GET['ago']) ? $_GET['ago'] : $this->endingDaysBack
            )->setRightTitle('For example, entering 365 days means you get all statistics the specified number of days up to one year ago.'),
            NumericField::create(
                'min',
                'Count treshold',
                isset($_GET['min']) ? $_GET['min'] : $this->defaultMinimum
            )->setRightTitle('Minimum number of searches for it to show up in the statistics. For example, enter five to show only phrases that were searched for at least five times during the specified period.')
        );
        $actions = FieldList::create(FormAction::create("run")->setTitle("show results"));
        $form = Form::create($this, "SearchFields", $fields, $actions, null);
        $form->setAttribute('method', 'get');
        $form->setAttribute('action', $this->Link());
        echo $form->forTemplate();
        echo '<style>
            div.field {margin-bottom: 20px;}
            .right {font-style:italics; color: #555;}
        </style>';
    }

    public function Link($action = null)
    {
        return '/dev/tasks/EcommerceTaskReviewSearches/';
    }
}
