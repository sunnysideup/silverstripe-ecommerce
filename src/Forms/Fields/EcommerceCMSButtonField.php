<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\FormField;
use SilverStripe\View\ViewableData;
class EcommerceCMSButtonField extends LiteralField
{
    /**
     * @param string  $name
     * @param mixed  $link (string|ViewableData|FormField)
     * @param string  $title
     * @param boolean $newWindow
     */
    public function __construct($name, $link, string $title, ?bool $newWindow = false)
    {
        $target = '';
        if ($newWindow) {
            $target = 'target="_blank"';
        }
        parent::__construct(
            $name,
            '
            <div class="form-group field readonly">
                <label class="form__field-label"></label>
                <div class="form__field-holder">
                    <p class="form-control-static readonly">
                        <a href="' . $link . '" ' . $target . ' class="btn action btn-outline-primary">
                            <span class="ui-button-text">
                                ' . $title . '
                            </span>
                        </a>
                    </p>
                </div>
            </div>

        '
        );
    }
}
