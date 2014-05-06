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
		Basic search statistics over the last XXX days.
		You can set XXX by using the get variable days.
		You can set the minimum number of treshold with the get variable min.
		For example: /dev/tasks/EcommerceTaskReviewSearches/?days=100&min=30";

	function run($request){
		$days = intval($this->getRequest()->getVar("days")-0);
		if(!$days) {
			$days = 100;
		}
		$countMin = intval($this->getRequest()->getVar("min")-0);
		$data = DB::query("SELECT COUNT(ID) count, Title FROM \"SearchHistory\" WHERE Created > ( NOW() - INTERVAL $days DAY ) GROUP BY \"Title\"  HAVING COUNT(ID) >= $countMin ORDER BY count DESC ");
		//we can not divide by zero! Minimum is 1.
		if(!$countMin) {
			$countMin++;
		}
		$v = "<h1>Search Phrases entered at least $countMin times during the last $days days</h1><table>";
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
		$v .= "<h1>A - Z</h1><table>";
		foreach($list as $key => $title) {
			$array = explode("-", $key);
			$multipliedWidth = $array[0] * $multiplier;
			$v .=' <tr><td style="text-align: right; width: 350px;">'.$title.'</td><td style="background-color: grey"><div style="width: '.$multipliedWidth.'px; background-color: #0066CC;">'.$array[0].'</div></td></tr>';
		}
		$v .= '</table>';
		echo $v;

	}

}

