<?php




/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog_DispatchElectronicOrder extends OrderStatusLog_Dispatch
{

    private static $db = array(
        'Link' => 'Text',
    );

    private static $singular_name = "Order Log Electronic Dispatch Entry";
    public function i18n_singular_name()
    {
        return _t("OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRY", "Order Log Electronic Dispatch Entry");
    }

    private static $plural_name = "Order Log Electronic Dispatch Entries";
    public function i18n_plural_name()
    {
        return _t("OrderStatusLog.ORDERLOGELECTRONICDISPATCHENTRIES", "Order Log Electronic Dispatch Entries");
    }
}
