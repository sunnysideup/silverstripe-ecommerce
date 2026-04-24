<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Model\Process;

use SilverStripe\ORM\DataObject;

class ReferralProcessLog extends DataObject
{
    private static $table_name = 'ReferralProcessLog';

    private static $db = [
        'Completed' => 'Boolean',

    ];
}
