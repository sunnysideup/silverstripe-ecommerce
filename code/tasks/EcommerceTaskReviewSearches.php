<?php

/**
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 **/

class EcommerceTaskReviewSearches extends BuildTask
{

    /**
     * number of days shown
     * @int
     */
    protected $defaultDays = 100;

    /**
     * minimum number of searches for
     * a particular keyword in order to show it at all
     * @int
     */
    protected $defaultMinimum = 5;

    /**
     * show up to XXX days ago
     * @int
     */
    protected $endingDaysBack = 0;

    /**
     * Standard (required) SS variable for BuildTasks
     * @var String
     */
    protected $title = "Search Statistics";

    /**
     * Standard (required) SS variable for BuildTasks
     * @var String
     */
    protected $description = "
		What did people search for on the website, you can use the days, min and ago GET variables to query different sets.";

    public function run($request)
    {
        $days = intval($request->getVar("days")-0);
        if (!$days) {
            $days = $this->defaultDays;
        }
        $countMin = intval($request->getVar("min")-0);
        if (!$countMin) {
            $countMin = $this->defaultMinimum;
        }
        $endingDaysBack = intval($request->getVar("ago")-0);
        if (!$endingDaysBack) {
            $endingDaysBack = $this->endingDaysBack;
        }
        $field = EcommerceSearchHistoryFormField::create("stats", $this->title)
            ->setNumberOfDays($days)
            ->setMinimumCount($countMin)
            ->setEndingDaysBack($endingDaysBack);
        echo $field->forTemplate();
        $arrayNumberOfDays = array(30, 365);
        $link = "/dev/tasks/EcommerceTaskReviewSearches/";
        for ($months = 0; $months++; $months < 36) {
            foreach ($arrayNumberOfDays as $numberOfDays) {
                $myLink = $link . "?ago=".floor($months * 30.5)."&amp;days=".$numberOfDays;
                DB::alteration_message("<a href=\"".$myLink."\">$months months ago, last $numberOfDays days</a>");
            }
        }
    }
}
