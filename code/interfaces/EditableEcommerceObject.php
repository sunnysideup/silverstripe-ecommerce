<?php
/**
 * describes any dataobject (apart from pages)
 * that is editable in the CMS.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 **/
interface EditableEcommerceObject
{
    /**
     * returns the link to edit the object.
     *
     * @param string | Null $action
     *
     * @return string
     */
    public function CMSEditLink($action = null);
}
