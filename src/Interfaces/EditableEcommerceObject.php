<?php

namespace Sunnysideup\Ecommerce\Interfaces;

/**
 * describes any dataobject (apart from pages)
 * that is editable in the CMS.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 */
interface EditableEcommerceObject
{
    /**
     * returns the link to edit the object.
     *
     * @param null|string $action
     *
     * @return string
     */
    public function CMSEditLink($action = null);
}
