<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use Sunnysideup\Ecommerce\Forms\ProductSearchForm;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;

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
     * @var bool|int
     */
    protected $showMoreLink = false;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name, '');
    }

    public function setNumberOfDays(int $days): self
    {
        $this->numberOfDays = (int) $days;

        return $this;
    }

    public function setMinimumCount(int $count): self
    {
        $this->minimumCount = (int) $count;

        return $this;
    }

    public function setShowMoreLink(bool $b): self
    {
        $this->showMoreLink = $b;

        return $this;
    }

    public function setEndingDaysBack(int $count): self
    {
        $this->endingDaysBack = (int) $count;

        return $this;
    }

    public function setMaxRows(int $number): self
    {
        $this->maxRows = $number;

        return $this;
    }

    public function setAddTitle(bool $b): self
    {
        $this->addTitle = $b;

        return $this;
    }

    public function setAddAtoZ(bool $b): self
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
        $row = [];
        $maxWidth = 100;
        $redirectToPage = DataObject::get_one(ProductGroupSearchPage::class);
        $title = $this->getContent();
        $totalNumberOfDaysBack = $this->numberOfDays + $this->endingDaysBack;
        $data = DB::query('
            SELECT COUNT(ID) myCount, "Title"
            FROM "SearchHistory"
            WHERE Created > ( NOW() - INTERVAL ' . $totalNumberOfDaysBack . ' DAY )
                AND Created < ( NOW() - INTERVAL ' . $this->endingDaysBack . " DAY )
                AND \"Title\" IS NOT NULL
                AND  \"Title\" <> ''
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
        if ($data->numRecords()) {
            $tableContent .= '
                <table class="highToLow" style="widht: 100%">';
            $list = [];
            foreach ($data as $key => $row) {
                ++$count;
                //for the highest count, we work out a max-width
                if (! $key) {
                    $maxWidth = $row['myCount'];
                }
                $multipliedWidthInPercentage = floor(($row['myCount'] / $maxWidth) * 100);
                $list[$row['myCount'] . '-' . $key] = $row['Title'];
                $link = $redirectToPage->Link() . '?searchfilter=Keyword~' . urlencode($row['Title']);
                $debugLink = $link . '&showdebug=1';
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
                    $link = $redirectToPage->Link(ProductSearchForm::class) . '?Keyword=' . urlencode($row['Title']) . '&action_doProductSearchForm=Search';
                    $debugLink = $link . '&showdebug=1';
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
        if (0 === $count) {
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

        return DBField::create_field(
            'HTMLText',
            $content
        );
    }
}
