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
    protected $minimumCount = 30;

    /**
     * maximum number of searches for the data to show up.
     *
     * @var int
     */
    protected $maxRows = 20;

    /**
     * @var bool
     */
    protected $addTitle = true;

    /**
     * @var bool
     */
    protected $addAtoZ = true;

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
     * @param int $days
     *
     * @return EcommerceSearchHistoryFormField
     */
    public function setNumberOfDays($days)
    {
        $this->numberOfDays = intval($days);

        return $this;
    }

    /**
     * @param int $count
     *
     * @return EcommerceSearchHistoryFormField
     */
    public function setMinimumCount($count)
    {
        $this->minimumCount = intval($count);

        return $this;
    }

    /**
     * @param int $b
     *
     * @return EcommerceSearchHistoryFormField
     */
    public function setShowMoreLink($b)
    {
        $this->showMoreLink = $b;

        return $this;
    }

    /**
     * @param int $count
     *
     * @return EcommerceSearchHistoryFormField
     */
    public function setEndingDaysBack($count)
    {
        $this->endingDaysBack = intval($count);

        return $this;
    }

    /**
     * @param int $number
     *
     * @return EcommerceSearchHistoryFormField
     */
    public function setMaxRows($number)
    {
        $this->maxRows = $number;

        return $this;
    }

    /**
     * @param $b
     *
     * @return EcommerceSearchHistoryFormField
     */
    public function setAddTitle($b)
    {
        $this->addTitle = $b;

        return $this;
    }

    /**
     * @param $b
     *
     * @return EcommerceSearchHistoryFormField
     */
    public function setAddAtoZ($b)
    {
        $this->addAtoZ = $b;

        return $this;
    }

    public function FieldHolder($properties = [])
    {
        return $this->Field($properties);
    }

    public function Field($properties = [])
    {
        $redirectToPage = DataObject::get_one('ProductGroupSearchPage');
        $title = $this->getContent();
        $totalNumberOfDaysBack = $this->numberOfDays + $this->endingDaysBack;
        $data = DB::query('
            SELECT COUNT(ID) myCount, "Title"
            FROM "SearchHistory"
            WHERE Created > ( NOW() - INTERVAL ' . $totalNumberOfDaysBack . ' DAY )
                AND Created < ( NOW() - INTERVAL ' . $this->endingDaysBack . " DAY )
            GROUP BY \"Title\"
            HAVING COUNT(\"ID\") >= {$this->minimumCount}
            ORDER BY myCount DESC
            LIMIT " . $this->maxRows . '
        ');
        if (! $this->minimumCount) {
            ++$this->minimumCount;
        }
        $content = '';
        $tableContent = '';
        if ($title && $this->addTitle) {
            $content .= '<h3>' . $title . '</h3>';
        }
        $content .= '
        <div id="SearchHistoryTableForCMS">
            <h3>
                Search Phrases'
                . ($this->minimumCount > 1 ? ', entered at least ' . $this->minimumCount . ' times' : '')
                . ($this->maxRows < 1000 ? ', limited to ' . $this->maxRows . ' entries, ' : '')
                . ' between ' . date('j-M-Y', strtotime('-' . $totalNumberOfDaysBack . ' days')) . ' and ' . date('j-M-Y', strtotime('-' . $this->endingDaysBack . ' days')) . '
            </h3>';
        $count = 0;
        if ($data && count($data)) {
            $tableContent .= '
                <table class="highToLow" style="widht: 100%">';
            $list = [];
            foreach ($data as $key => $row) {
                $count++;
                //for the highest count, we work out a max-width
                if (! $key) {
                    $maxWidth = $row['myCount'];
                }
                $multipliedWidthInPercentage = floor(($row['myCount'] / $maxWidth) * 100);
                $list[$row['myCount'] . '-' . $key] = $row['Title'];
                $link = $redirectToPage->Link('ProductSearchForm') . '?Keyword=' . urlencode($row['Title']) . '&action_doProductSearchForm=Search';
                $debugLink = $link . '&DebugSearch=1';
                $tableContent .= '
                    <tr>
                        <td style="text-align: right; width: 30%; padding: 5px;">
                            <a href="' . $link . '">' . $row['Title'] . '</a>
                        </td>
                        <td style="background-color: silver;  padding: 5px; width: 70%;">
                            <div style="width: ' . $multipliedWidthInPercentage . '%; background-color: #C51162; color: #fff;">' . $row['myCount'] . '</div>
                        </td>
                        <td style="background-color: silver; width: 20px">
                            <a href="' . $debugLink . '">☕</a>
                        </td>
                    </tr>';
            }
            $tableContent .= '
                </table>';
            if ($count && $this->addAtoZ) {
                asort($list);
                $tableContent .= '
                    <h3>A - Z</h3>
                    <table class="aToz" style="widht: 100%">';
                foreach ($list as $key => $title) {
                    $link = $redirectToPage->Link('ProductSearchForm') . '?Keyword=' . urlencode($row['Title']) . '&action_doProductSearchForm=Search';
                    $debugLink = $link . '&DebugSearch=1';
                    $array = explode('-', $key);
                    $multipliedWidthInPercentage = floor(($array[0] / $maxWidth) * 100);
                    $tableContent .= '
                        <tr>
                            <td style="text-align: right; width: 30%; padding: 5px;">
                                <a href="' . $link . '">' . $title . '</a>
                            </td>
                            <td style="background-color: silver;  padding: 5px; width: 70%">
                                <div style="width: ' . $multipliedWidthInPercentage . '%; background-color: #004D40; color: #fff;">' . trim($array[0]) . '</div>
                            </td>
                            <td style="background-color: silver; width: 20px">
                                <a href="' . $debugLink . '">☕</a>
                            </td>
                        </tr>';
                }
                $tableContent .= '
                    </table>';
            }
        }
        if ($count === 0) {
            //we replace table content here...
            $tableContent = '<p class="warning message">No searches found.</p>';
        }
        $content .= $tableContent;
        if ($this->showMoreLink) {
            $content .= '
            <p>
                <a href="/dev/tasks/EcommerceTaskReviewSearches/">Query more resuts</a>
            </p>';
        }

        $content .= '
        </div>';

        return $content;
    }
}
