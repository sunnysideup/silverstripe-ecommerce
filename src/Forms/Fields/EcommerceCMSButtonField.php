<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ViewableData;

class EcommerceCMSButtonField extends LiteralField
{
    /**
     * @param string $link  a URL (it is escaped here)
     * @param string $title HTML: the caller has to escape anything that is not fixed text (e.g. names of customers)
     */
    public function __construct(string $name, string $link, string $title, ?bool $newWindow = false)
    {
        $link = Convert::raw2att($link);
        $target = '';
        if ($newWindow) {
            $target = 'target="_blank"';
        }
        $html = <<<html
                    <div class="form-group field readonly">
                        <label class="form__field-label"></label>
                        <div class="form__field-holder">
                            <p class="form-control-static readonly">
                                <a href="{$link}" {$target} class="btn action btn-outline-primary">
                                    <span class="ui-button-text">
                                        {$title}
                                    </span>
                                </a>
                            </p>
                        </div>
                    </div>

html;

        $html = DBField::create_field('HTMLText', $html);
        parent::__construct(
            $name,
            $html
        );
    }
}
