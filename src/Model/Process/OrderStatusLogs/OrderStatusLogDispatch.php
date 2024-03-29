<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogDispatch
 *
 */
class OrderStatusLogDispatch extends OrderStatusLog
{
    private static $defaults = [
        'InternalUseOnly' => true,
    ];

    private static $singular_name = 'Order Log Dispatch Entry';

    private static $plural_name = 'Order Log Dispatch Entries';

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ORDERLOGDISPATCHENTRY', 'Order Log Dispatch Entry');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.ORDERLOGDISPATCHENTRIES', 'Order Log Dispatch Entries');
    }
}
