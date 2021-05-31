<?php

namespace Sunnysideup\Ecommerce\Email;

use Pelago\Emogrifier\CssInliner;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @Description: Email specifically for communicating with customer about order.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: email
 */
abstract class OrderEmail extends Email
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var bool
     */
    protected $resend = false;

    /**
     * @var bool
     */
    private static $send_all_emails_plain = false;

    /**
     * @var string
     */
    private static $css_file_location = 'vendor/sunnysideup/ecommerce/client/css/OrderReport.css';

    /**
     * @var bool
     */
    private static $copy_to_admin_for_all_emails = true;

    /**
     * turns an html document into a formatted html document
     * using the emogrify method.
     *
     * @param string $html
     *
     * @return string HTML
     */
    public static function emogrify_html($html)
    {
        //get required files
        // UPGRADE TODO: find better solution for the following (without hardcoded path)
        $cssFileLocation = Director::baseFolder() . '/' . EcommerceConfig::get(OrderEmail::class, 'css_file_location');
        $cssFileHandler = fopen($cssFileLocation, 'r');
        $css = fread($cssFileHandler, filesize($cssFileLocation));
        fclose($cssFileHandler);
        $html = CssInliner::fromHtml($html)
            ->inlineCss($css)
            ->render()
        ;
        //make links absolute!
        return HTTP::absoluteURLs($html);
    }

    /**
     * returns the standard from email address (e.g. the shop admin email address).
     *
     * @return string
     */
    public static function get_from_email()
    {
        $ecommerceConfig = EcommerceConfig::inst();
        if ($ecommerceConfig && $ecommerceConfig->ReceiptEmail) {
            $email = $ecommerceConfig->ReceiptEmail;
        } else {
            $email = Email::config()->admin_email;
        }

        return trim($email);
    }

    /**
     * returns the subject for the email (doh!).
     *
     * @return string
     */
    public static function get_subject()
    {
        $siteConfig = SiteConfig::current_site_config();
        if ($siteConfig && $siteConfig->Title) {
            return _t('OrderEmail.SALEUPDATE', 'Sale Update for Order #[OrderNumber] from ') . $siteConfig->Title;
        }

        return _t('OrderEmail.SALEUPDATE', 'Sale Update for Order #[OrderNumber] ');
    }

    /**
     * set the order associated with the email.
     *
     * @param Order $order - the order to which the email relates
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    /**
     * sets resend to true, which means that the email
     * is sent even if it has already been sent.
     *
     * @param mixed $resend
     */
    public function setResend($resend = true)
    {
        $this->resend = $resend;
    }

    /**
     * @param null|string $messageID      - ID for the message, you can leave this blank
     * @param bool        $returnBodyOnly - rather than sending the email, only return the HTML BODY
     *
     * @return bool - TRUE for success and FALSE for failure
     */
    public function send($messageID = null, $returnBodyOnly = false)
    {
        if (! $this->order) {
            user_error('Must set the order (OrderEmail::setOrder()) before the message is sent (OrderEmail::send()).', E_USER_NOTICE);
        }
        if (! $this->subject) {
            $this->subject = self::get_subject();
        }
        $this->subject = str_replace('[OrderNumber]', $this->order->ID, $this->subject);
        if (! $this->hasBeenSent() || ($this->resend)) {
            if (EcommerceConfig::get(OrderEmail::class, 'copy_to_admin_for_all_emails') && ($this->to !== self::get_from_email())) {
                if ($memberEmail = self::get_from_email()) {
                    $array = [$memberEmail];
                    if ($bcc = $this->getBcc()) {
                        $array[] = $bcc;
                    }
                    $this->setBcc(implode(', ', $array));
                }
            }
            //last chance to adjust
            $this->extend('adjustOrderEmailSending', $this, $order);
            if ($returnBodyOnly) {
                return $this->getBody();
            }

            if (EcommerceConfig::get(OrderEmail::class, 'send_all_emails_plain')) {
                $result = parent::sendPlain();
            } else {
                $result = parent::send();
            }

            $orderEmailRecord = $this->createRecord($result);
            if (Director::isDev()) {
                $result = true;
            }
            $orderEmailRecord->Result = (bool) $result;
            $orderEmailRecord->write();

            return $result;
        }

        return false;
    }

    /**
     * converts an Email to A Varchar.
     *
     * @param string|array $email - email address
     *
     * @return string - returns email address without &gt; and &lt;
     */
    public function emailToVarchar($email)
    {
        $emailString = '';
        if(is_string($email)){
            $emailString = $email;
        }
        else if(is_array($email)){
            $count = 0;
            foreach ($email as $address) {
                if($count){
                    $emailString .= ', ';
                }
                $emailString .= $address;
                $count++;
            }
        }
        return str_replace(['<', '>', '"', "'"], ' - ', $emailString);
    }

    /**
     * Checks if an email has been sent for this Order for this status (order step).
     */
    public function hasBeenSent(): bool
    {
        $orderStep = $this->order->Status();
        if (is_a($orderStep, EcommerceConfigClassNames::getName(OrderStep::class))) {
            return $orderStep->hasBeenSent($this->order);
        }

        return false;
    }

    /**
     * @param mixed $result
     *
     * @return OrderEmailRecord
     */
    protected function createRecord($result)
    {
        $orderEmailRecord = OrderEmailRecord::create();
        $from = is_array($this->from) ? array_key_first($this->from) : $this->from;
        $to = is_array($this->to) ? array_key_first($this->to) : $this->to;
        $orderEmailRecord->From = $this->emailToVarchar($from);
        $orderEmailRecord->To = $this->emailToVarchar($to);
        if ($this->getCc()) {
            $orderEmailRecord->To .= ', CC: ' . $this->emailToVarchar($this->getCc());
        }
        if ($this->getBcc()) {
            $orderEmailRecord->To .= ', BCC: ' . $this->emailToVarchar($this->getBcc());
        }
        //always set result to try if
        $orderEmailRecord->Subject = $this->subject;
        if (! $result) {
            if (Director::isDev()) {
                $orderEmailRecord->Subject .= _t('OrderEmail.FAKELY_RECORDED_AS_SENT', ' - FAKELY RECORDED AS SENT ');
            }
        }
        $orderEmailRecord->Content = $this->body;
        $orderEmailRecord->Result = $result ? 1 : 0;
        $orderEmailRecord->OrderID = $this->order->ID;
        $orderEmailRecord->OrderStepID = $this->order->StatusID;
        if ($sendAllEmailsTo = Config::inst()->get(Email::class, 'send_all_emails_to')) {
            $orderEmailRecord->To .=
                _t('OrderEmail.ACTUALLY_SENT_TO', ' | actually sent to: ')
                . $sendAllEmailsTo
                . _t('OrderEmail.CONFIG_EXPLANATION', ' - (Email::send_all_emails_to)');
        }
        $orderEmailRecord->write();

        return $orderEmailRecord;
    }

    /**

     * Render the email
     * @param bool $plainOnly Only render the message as plain text
     * @return $this
     */
    public function render($plainOnly = false)
    {
        parent::render($plainOnly);
        //moves CSS to inline CSS in email.
        if (!$plainOnly) {
            $this->body = $this->body ? self::emogrify_html($this->body) : '';
        }
    }

}
