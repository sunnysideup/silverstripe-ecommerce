<?php


class EcommerceCMSButtonField extends LiteralField
{

    function __construct($name, $link, $title)
    {
        return parent::__construct(
            $name,
            '
            <h3>
                <a href="'.$link.'" class="action ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
                    <span class="ui-button-text">
                        '.$title.'
                    </span>
                </a>
            </h3>

        ');
    }

}
