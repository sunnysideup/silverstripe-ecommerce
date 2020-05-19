<?php

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
        echo $controller->RenderWith($controller->class);
    }
}

