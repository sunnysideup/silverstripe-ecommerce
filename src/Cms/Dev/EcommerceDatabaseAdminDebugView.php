<?php

namespace Sunnysideup\Ecommerce\Cms\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\DebugView;

class EcommerceDatabaseAdminDebugView extends DebugView
{
    public function writePreOutcome()
    {
        echo "<div id='TaskHolder' style=\"background-color: #e8e8e8; border-radius: 15px; margin: 20px; padding: 20px\">";
    }

    public function writePostOutcome()
    {
        echo '</div>';
    }

    public function writeContent(Controller $controller)
    {

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: ->RenderWith( (ignore case)
         * NEW: ->RenderWith( (COMPLEX)
         * EXP: Check that the template location is still valid!
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        echo $controller->RenderWith($controller->class);
    }
}
