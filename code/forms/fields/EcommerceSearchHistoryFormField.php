<?php

class EcommerceSearchHistoryFormField extends LiteralField {

	/**
	 * total number days to search back
	 * @var Int
	 */
	protected $numberOfDays = 100;

	/**
	 * minimum number of searches for the data to show up
	 * @var Int
	 */
	protected $minimumCount = 1;


	function __construct($name, $content = "") {
		$data = DB::query("
			SELECT COUNT(ID) myCount, \"Title\"
			FROM \"SearchHistory\"
			WHERE Created > ( NOW() - INTERVAL ".$this->numberOfDays." DAY ) GROUP BY \"Title\"
			HAVING COUNT(\"ID\") >= $this->minimumCount
			ORDER BY myCount DESC ");
		if(!$this->minimumCount) $this->minimumCount++;
		$content .= "<!-- start of field --><div id=\"SearchHistoryTableForCMS\"><h1>Search Phrases entered at least $this->minimumCount times during the last ".$this->numberOfDays." days</h1><table>";
		$list = array();
		foreach($data as $key => $row) {
			if(!$key) {
				$multiplier = 700 / $row["myCount"];
			}
			$multipliedWidth = floor($row["myCount"]*$multiplier);
			$list[$row["myCount"]."-".$key] = $row["Title"];
			$content .=' <tr><td style="text-align: right; width: 350px; padding: 5px;">'.$row["Title"].'</td><td style="background-color: silver;  padding: 5px;"><div style="width: '.$multipliedWidth.'px; background-color: #0066CC; color: #fff;">'.$row["myCount"].'</div></td></tr>';
		}
		$content .= '</table>';
		asort($list);
		$content .= "<h1>A - Z</h1><table>";
		foreach($list as $key => $title) {
			$array = explode("-", $key);
			$multipliedWidth = $array[0]*$multiplier;
			$content .=' <tr><td style="text-align: right; width: 350px; padding: 5px;">'.$title.'</td><td style="background-color: silver;  padding: 5px;"><div style="width: '.$multipliedWidth.'px; background-color: #0066CC; color: #fff;">'.$array[0].'</div></td></tr>';
		}
		$content .= '</table></div>';
		parent::__construct($name, $content);
	}

	/**
	 * @param Int
	 * @return Field
	 */
	public function setNumberOfDays($days) {
		$this->numberOfDays = intval($days);
		return $this;
	}

	/**
	 * @param Int
	 * @return Field
	 */
	public function setMinimumCount($count) {
		$this->minimumCount = intval($count);
		return $this;
	}

}
