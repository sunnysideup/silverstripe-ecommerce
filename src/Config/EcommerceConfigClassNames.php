<?php

namespace Sunnysideup\Ecommerce\Config;

/*
 * This class returns the Ajax Definitions class.
 * The Ajax Definitions class is an object that contains all the values
 * for ajax references in the templates.
 *
 * We need to have one per classname (e.g. Product)and requestor (Product A with ID = 1)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration
 *
 */
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

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
 */
class EcommerceConfigClassNames
{
    use Extensible;
    use Injectable;
    use Configurable;

    /**
     * @todo: make this more sophisticated / customisable
     *
     * @return
     */
    public static function getName(string $class)
    {
        return $class;
    }
}
