<?php


/**
 * @description: CMS management for everything you have sold and all related data (e.g. logs, payments)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class SalesAdminExtras extends ModelAdminEcommerceBaseClass
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-advanced';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Sales Details';

    /**
     * standard SS variable.
     *
     * @var int
     */
    private static $menu_priority = 3.11;

    /**
     * Change this variable if you don't want the Import from CSV form to appear.
     * This variable can be a boolean or an array.
     * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo').
     */
    public $showImportForm = false;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'ecommerce/images/icons/money-file.gif';

    public function init()
    {
        parent::init();
        Requirements::javascript('ecommerce/javascript/EcomBuyableSelectField.js');
    }

    /**
     * @return DataList
     */
    public function getList()
    {
        $list = parent::getList();
        if (singleton($this->modelClass) instanceof Order) {
            $list = $list->exclude(array("StatusID" => 0));
        }
        return $list;
    }

}
