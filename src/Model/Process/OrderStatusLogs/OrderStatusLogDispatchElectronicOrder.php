<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogDispatchElectronicOrder
 *
 * @property string $Link
 */
class OrderStatusLogDispatchElectronicOrder extends OrderStatusLogDispatch
{
    private static $table_name = 'OrderStatusLogDispatchElectronicOrder';

    private static $db = [
        'Link' => 'Text',
    ];

    private static $singular_name = 'Order Log Electronic Dispatch Entry';

    private static $plural_name = 'Order Log Electronic Dispatch Entries';

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRY', 'Order Log Electronic Dispatch Entry');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRIES', 'Order Log Electronic Dispatch Entries');
    }
}
