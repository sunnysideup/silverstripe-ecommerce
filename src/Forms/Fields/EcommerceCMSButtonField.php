<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\LiteralField;

class EcommerceCMSButtonField extends LiteralField
{
    public function __construct($name, $link, $title, $newWindow = false)
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
