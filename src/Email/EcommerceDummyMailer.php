<?php

namespace Sunnysideup\Ecommerce\Email;

class EcommerceDummyMailer
{
    /**
     * FAKE Send a plain-text email.
     *
     * @param mixed $to
     * @param mixed $from
     * @param mixed $subject
     * @param mixed $plainContent
     * @param mixed $attachedFiles
     * @param mixed $customheaders
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
     * @param mixed $to
     * @param mixed $from
     * @param mixed $subject
     * @param mixed $htmlContent
     * @param mixed $attachedFiles
     * @param mixed $customheaders
     * @param mixed $plainContent
     *
     * @return bool
     */
    public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false)
    {
        return true;
    }
}
