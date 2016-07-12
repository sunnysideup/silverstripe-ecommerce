<?php

class EcommerceSearchHistoryFormField extends LiteralField
{
    /**
     * total number days to search back.
     *
     * @var int
     */
    protected $numberOfDays = 100;

    /**
     * how many days ago the data-analysis should end.
     *
     * @var int
     */
    protected $endingDaysBack = 0;

    /**
     * minimum number of searches for the data to show up.
     *
     * @var int
     */
    protected $minimumCount = 1;
    /**
     * minimum number of searches for the data to show up.
     *
     * @var bool
     */
    protected $showMoreLink = false;

    public function __construct($name, $title = '')
    {
        return parent::__construct($name, $title);
    }

    /**
     * @param int
     *
     * @return Field
     */
    public function setNumberOfDays($days)
    {
        $this->numberOfDays = intval($days);

        return $this;
    }

    /**
     * @param int
     *
     * @return Field
     */
    public function setMinimumCount($count)
    {
        $this->minimumCount = intval($count);

        return $this;
    }

    /**
     * @param int
     *
     * @return Field
     */
    public function setShowMoreLink($b)
    {
        $this->showMoreLink = $b;

        return $this;
    }

    /**
     * @param int
     *
     * @return Field
     */
    public function setEndingDaysBack($count)
    {
        $this->endingDaysBack = intval($count);

        return $this;
    }

    public function  FieldHolder($properties = Array())
    {
        return $this->Field($properties);
    }

    public function Field($properties = array())
    {
        $name = $this->getName();
        $title = $this->title;
        $totalNumberOfDaysBack = $this->numberOfDays + $this->endingDaysBack;
        $data = DB::query('
            SELECT COUNT(ID) myCount, "Title"
            FROM "SearchHistory"
            WHERE Created > ( NOW() - INTERVAL '.$totalNumberOfDaysBack.' DAY )
                AND Created < ( NOW() - INTERVAL '.$this->endingDaysBack." DAY )
            GROUP BY \"Title\"
            HAVING COUNT(\"ID\") >= $this->minimumCount
            ORDER BY myCount DESC
        ");
        if (!$this->minimumCount) {
            ++$this->minimumCount;
        }
        $content = '';
        $tableContent = '';
        if ($title) {
            $content .= '<h2>'.$title.'</h2>';
        }
        $content .= '
        <div id="SearchHistoryTableForCMS">
            <h3>
                Search Phrases entered at least '.$this->minimumCount.' times
                between '.date('j-M-Y', strtotime('-'.$totalNumberOfDaysBack.' days')).'
                and '.date('j-M-Y', strtotime('-'.$this->endingDaysBack.' days')).'
            </h3>';
        $count = 0;
        if($data && count($data)) {
            $tableContent .= '
                <table class="highToLow" style="widht: 100%">';
            $list = array();
            foreach ($data as $key => $row) {
                $count++;
                //for the highest count, we work out a max-width
                if (!$key) {
                    $maxWidth = $row['myCount'];
                }
                $multipliedWidthInPercentage = floor(($row['myCount'] / $maxWidth) * 100);
                $list[$row['myCount'].'-'.$key] = $row['Title'];
                $tableContent .= '
                    <tr>
                        <td style="text-align: right; width: 30%; padding: 5px;">'.$row['Title'].'</td>
                        <td style="background-color: silver;  padding: 5px; width: 70%;">
                            <div style="width: '.$multipliedWidthInPercentage.'%; background-color: #0066CC; color: #fff;">'.$row['myCount'].'</div>
                        </td>
                    </tr>';
            }
            $tableContent .= '
                </table>';
            if($count) {
                asort($list);
                $tableContent .= '
                    <h3>A - Z</h3>
                    <table class="aToz" style="widht: 100%">';
                foreach ($list as $key => $title) {
                    $array = explode('-', $key);
                    $multipliedWidthInPercentage = floor(($array[0] / $maxWidth) * 100);
                    $tableContent .= '
                        <tr>
                            <td style="text-align: right; width: 30%; padding: 5px;">'.$title.'</td>
                            <td style="background-color: silver;  padding: 5px; width: 70%">
                                <div style="width: '.$multipliedWidthInPercentage.'%; background-color: #0066CC; color: #fff;">'.trim($array[0]).'</div>
                            </td>
                        </tr>';
                }
                $tableContent .= '
                    </table>';
            }
        }
        if($count === 0) {
            //we replace table content here...
            $tableContent = '<p class="warning message">No searches found.</p>';
        }
        if($this->showMoreLink) {
            $content .= $tableContent.'
            <p>
                <a href="/dev/tasks/EcommerceTaskReviewSearches/">Query more resuts</a>
            </p>';
        }
        $content .= '
        </div>';

        return $content;
    }
}
