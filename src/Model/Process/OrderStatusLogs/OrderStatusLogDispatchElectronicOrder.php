<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs;

use Override;

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

    #[Override]
    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRY', 'Order Log Electronic Dispatch Entry');
    }

    #[Override]
    public function plural_name()
    {
        return _t('OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRIES', 'Order Log Electronic Dispatch Entries');
    }
}
