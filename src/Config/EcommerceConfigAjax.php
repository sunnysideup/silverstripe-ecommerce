<?php

namespace Sunnysideup\Ecommerce\Config;

/**
 * This class returns the Ajax Definitions class.
 * The Ajax Definitions class is an object that contains all the values
 * for ajax references in the templates.
 *
 * We need to have one per classname (e.g. Product)and requestor (Product A with ID = 1)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration

 **/
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

/**
 * This class returns the Ajax Definitions class.
 * The Ajax Definitions class is an object that contains all the values
 * for ajax references in the templates.
 *
 * We need to have one per classname (e.g. Product)and requestor (Product A with ID = 1)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration
 **/
class EcommerceConfigAjax
{
    use Extensible;
    use Injectable;
    use Configurable;

    /**
     * implements singleton pattern so that there is only ever one instance
     * of this class.
     * This is usually defined as $singleton[$ClassName][$Requestor->ID].
     *
     * @static object
     */
    private static $singleton = [];

    /**
     * @var string
     */
    private static $definitions_class_name = EcommerceConfigAjaxDefinitions::class;

    /**
     * @var string
     */
    private static $cart_js_file_location = 'client/javascript/EcomCart.js';

    /**
     * @var string
     */
    private static $dialogue_js_file_location = 'client/javascript/jquery.colorbox-min.js';

    /**
     * Returns the singleton instance of the Ajax Config definitions class.
     * This class basically contains a bunch of methods that return
     * IDs and Classes for use with AJAX.
     *
     * @param DataObject $requestor the object requesting the Ajax Config Definitions
     *
     * @return EcommerceConfigAjaxDefinitions (or other object)
     */
    public static function get_one($requestor)
    {
        if (! isset(self::$singleton[$requestor->ClassName][$requestor->ID])) {
            $className = EcommerceConfig::get(EcommerceConfigAjax::class, 'definitions_class_name');
            self::$singleton[$requestor->ClassName][$requestor->ID] = new $className();
            self::$singleton[$requestor->ClassName][$requestor->ID]->setRequestor($requestor);
        }
        return self::$singleton[$requestor->ClassName][$requestor->ID];
    }
}
