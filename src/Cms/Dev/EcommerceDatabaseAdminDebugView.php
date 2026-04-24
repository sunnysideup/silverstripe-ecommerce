<?php

declare(strict_types=1);

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
        echo $controller->RenderWith($controller::class);
    }

    public function writeFooter()
    {
        echo '<p><a href="' . Controller::join_links(Controller::curr()->Link(), 'run') . '">Run Task</a></p>';
    }
}
