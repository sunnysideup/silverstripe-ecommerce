<?php

class EcommerceSearchHistoryFormField extends LiteralField {

	/**
	 * total number days to search back
	 * @var Int
	 */
	protected $numberOfDays = 100;

	/**
	 * how many days ago the data-analysis should end
	 * @var Int
	 */
	protected $endingDaysBack = 0;

	/**
	 * minimum number of searches for the data to show up
	 * @var Int
	 */
	protected $minimumCount = 1;

	function __construct($name, $title = "") {
		return parent::__construct($name, $title);
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

	/**
	 * @param Int
	 * @return Field
	 */
	public function setEndingDaysBack($count) {
		$this->endingDaysBack = intval($count);
		return $this;
	}

	public function Field($properties = array()) {
		$name = $this->getName();
		$title = $this->title;
		$totalNumberOfDaysBack = $this->numberOfDays + $this->endingDaysBack;
		$data = DB::query("
			SELECT COUNT(ID) myCount, \"Title\"
			FROM \"SearchHistory\"
			WHERE Created > ( NOW() - INTERVAL ".$totalNumberOfDaysBack." DAY )
				AND Created < ( NOW() - INTERVAL ".$this->endingDaysBack." DAY )
			GROUP BY \"Title\"
			HAVING COUNT(\"ID\") >= $this->minimumCount
			ORDER BY myCount DESC
		");
		if(!$this->minimumCount) {
			$this->minimumCount++;
		}
		$content = "";
		if($title) {
			$content .= "<h2>".$title."</h2>";
		}
		$content .= "
		<div id=\"SearchHistoryTableForCMS\">
			<h3>Search Phrases entered at least ".$this->minimumCount." times between ".date("Y-M-d", strtotime("-".$totalNumberOfDaysBack." days"))." and ".date("Y-M-d", strtotime("-".$this->endingDaysBack." days"))."</h3>
			<table class=\"highToLow\" style=\"widht: 100%\">";
		$list = array();
		foreach($data as $key => $row) {
			//for the highest count, we work out a max-width
			if(!$key) {
				$maxWidth = $row["myCount"];
			}
			$multipliedWidthInPercentage = floor(($row["myCount"] / $maxWidth)* 100);
			$list[$row["myCount"]."-".$key] = $row["Title"];
			$content .='
				<tr>
					<td style="text-align: right; width: 30%; padding: 5px;">'.$row["Title"].'</td>
					<td style="background-color: silver;  padding: 5px; width: 70%;">
						<div style="width: '.$multipliedWidthInPercentage.'%; background-color: #0066CC; color: #fff;">'.$row["myCount"].'</div>
					</td>
				</tr>';
		}
		$content .= '
			</table>';
		asort($list);
		$content .= "
			<h3>A - Z</h3>
			<table class=\"aToz\" style=\"widht: 100%\">";
		foreach($list as $key => $title) {
			$array = explode("-", $key);
			$multipliedWidthInPercentage = floor(($array[0] / $maxWidth)* 100);
			$content .='
				<tr>
					<td style="text-align: right; width: 30%; padding: 5px;">'.$title.'</td>
					<td style="background-color: silver;  padding: 5px; width: 70%">
						<div style="width: '.$multipliedWidthInPercentage.'%; background-color: #0066CC; color: #fff;">'.trim($array[0]).'</div>
					</td>
				</tr>';
		}
		$content .= '
			</table>
		</div>';
		return "---------------------".$content;
	}

}
