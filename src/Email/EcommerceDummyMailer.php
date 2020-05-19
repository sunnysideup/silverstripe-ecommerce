<?php

namespace Sunnysideup\Ecommerce\Email;

class EcommerceDummyMailer
{
    /**
     * FAKE Send a plain-text email.
     *
     * @return bool
     */
    public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false)
    {
        return true;
    }

    /**
     * FAKE Send a multi-part HTML email.
     *
     * @return bool
     */
    public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false)
    {
        return true;
    }
}
