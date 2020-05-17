<?php

/**
 * @Description: This class handles the error email which can be sent
 * to the admin only if something untowards is happening.
 *
 * At present, this class is used to send any email that goes to admin only.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: email

 **/
class OrderErrorEmail extends OrderEmail
{
    /**
     * @param string $ss_template The name of the used template (without *.ss extension)
     */
    protected $ss_template = 'OrderErrorEmail';
}
