<?php

class EcommerceTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected $requiredExtensions = [];

    public function TestDevBuild()
    {
        $exitStatus = shell_exec('php vendor/bin/sake dev/build flush=all  > dev/null; echo $?');
        $exitStatus = intval(trim($exitStatus));
        $this->assertEquals(0, $exitStatus);
    }
}
