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
            <h3>
                <a href="' . $link . '" ' . $target . ' class="action ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
                    <span class="ui-button-text">
                        ' . $title . '
                    </span>
                </a>
            </h3>

        '
        );
    }
}
