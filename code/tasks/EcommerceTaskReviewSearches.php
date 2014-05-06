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

class EcommerceTaskReviewSearches extends BuildTask{

	private $defaultDays = 100;

	private $defaultMinimum = 5;

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
		What did people search for on the website over the last XXX days...";

	function run($request){
		$days = intval($request->getVar("days")-0);
		if(!$days) {
			$days = $this->defaultDays;
		}
		$countMin = intval($request->getVar("min")-0);
		if(!$countMin) {
			$countMin = $this->defaultMinimum;
		}
		$data = DB::query("SELECT COUNT(ID) count, Title FROM \"SearchHistory\" WHERE Created > ( NOW() - INTERVAL $days DAY ) GROUP BY \"Title\"  HAVING COUNT(ID) >= $countMin ORDER BY count DESC ");
		$v = "
		<h3>Settings</h3>
		<p>You can set the number of days by using the get variable <i>days</i>.</p>
		<p>You can set the minimum number of treshold with the get variable <i>min</i>.</p>
		<p>For example: ".Director::absoluteBaseURL()."/dev/tasks/EcommerceTaskReviewSearches/?<strong>days</strong>=100&<strong>min</strong>=30</p>
		<h3>Search Phrases entered at least $countMin times during the last $days days</h3>
		<table>";
		$list = array();
		foreach($data as $key => $row) {
			if(!$key) {
				$multiplier = 700 / $row["count"];
			}
			$multipliedWidth = floor($row["count"] * $multiplier);
			$list[$row["count"]."-".$key] = $row["Title"];
			$v .=' <tr><td style="text-align: right; width: 350px;">'.$row["Title"].'</td><td style="background-color: grey"><div style="width: '.$multipliedWidth.'px; background-color: #0066CC;">'.$row["count"].'</div></td></tr>';
		}
		$v .= '</table>';
		asort($list);
		$v .= "
			<h3>A - Z</h3>
			<table>";
		foreach($list as $key => $title) {
			$array = explode("-", $key);
			$multipliedWidth = $array[0] * $multiplier;
			$v .=' <tr><td style="text-align: right; width: 350px;">'.$title.'</td><td style="background-color: grey"><div style="width: '.$multipliedWidth.'px; background-color: #0066CC;">'.$array[0].'</div></td></tr>';
		}
		$v .= '</table>';
		echo $v;

	}

}

