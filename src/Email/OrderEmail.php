<?php

namespace Sunnysideup\Ecommerce\Email;

use Pelago\Emogrifier\CssInliner;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\SiteConfig\SiteConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @Description: Email specifically for communicating with customer about order.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
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

        return trim((string) $email);
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



    public function send(): void
    {
        $this->sendInner(false);
    }


    public function getBodyOnly(): string
    {
        return $this->sendInner(true);
    }

    /**
     *
     */
    protected function sendInner(?bool $returnBodyOnly = false): ?string
    {
        if (!$this->order) {
            user_error('Must set the order (OrderEmail::setOrder()) before the message is sent (OrderEmail::send()).', E_USER_NOTICE);
        }
        $this->fixupSubject();
        if (!$this->hasBeenSent() || ($this->resend)) {
            if (EcommerceConfig::get(OEmailrderEmail::class, 'copy_to_admin_for_all_emails') && ($this->getTo() !== self::get_from_email())) {
                $memberEmail = self::get_from_email();
                if ($memberEmail) {
                    $array = [$memberEmail];
                    $bcc = $this->getBcc();
                    if ($bcc) {
                        $array[] = $bcc;
                    }
                    $this->setBcc(implode(', ', $array));
                }
            }
            //last chance to adjust
            $this->extend('adjustOrderEmailSending', $this, $order);
            if ($returnBodyOnly) {
                return (string) $this->getHtmlBody();
            }
            if (EcommerceConfig::get(OrderEmail::class, 'send_all_emails_plain')) {
                parent::sendPlain();
            } else {
                // see parent::send()
                parent::send();
            }

            $result = $this->createRecord();

        }
        return null;

    }


    /**
     * @param resource|string|null $body
     *
     * @return $this
     */
    public function html($body, string $charset = 'utf-8'): static
    {
        if (null !== $body && is_string($body)) {
            $body = self::emogrify_html($body);
        }
        return parent::html($body, $charset);
    }

    protected function fixupSubject()
    {
        if (!$this->getSubject()) {
            $this->setSubject(self::get_subject());
        }
        $this->setSubject(str_replace('[OrderNumber]', $this->order->ID, (string) $this->getSubject()));
    }


    /**
     * converts an Email to A Varchar.
     *
     * @param array|string $email - email address
     *
     * @return string - returns email address without &gt; and &lt;
     */
    public function emailToVarchar($email)
    {
        $emailString = '';
        if (is_string($email)) {
            $emailString = (string) $email;
        } elseif (is_array($email)) {
            $count = 0;
            foreach ($email as $key => $address) {
                if ($count) {
                    $emailString .= ', ';
                }
                $emailString .= (string) $key . (string) $address;
                ++$count;
            }
        }

        return trim(str_replace(['<', '>', '"', "'"], ' - ', $emailString));
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
     * Render the email.
     * $plainOnly: Only render the message as plain tex.
     *
     * @param mixed $plainOnly
     *
     * @return $this
     */
    public function render($plainOnly = false): self
    {
        //force bool for parent for linting.
        $plainOnly = (bool) $plainOnly;
        parent::render($plainOnly);
        //moves CSS to inline CSS in email.
        if (!$plainOnly) {
            $html = (string) ($this->getHtmlBody() ?: '');
            $this->setBody($html);
        }

        return $this;
    }

    /**
     * @param mixed $result
     */
    protected function createRecord(): OrderEmailRecord
    {
        $orderEmailRecord = OrderEmailRecord::create();
        $from = is_array($this->getFrom()) ? array_key_first($this->getFrom()) : $this->getFrom();
        $to = is_array($this->getTo()) ? array_key_first($this->getTo()) : $this->getTo();
        $orderEmailRecord->From = $this->emailToVarchar($from);
        $orderEmailRecord->To = $this->emailToVarchar($to);
        if ($this->getCc()) {
            $orderEmailRecord->To .= ', CC: ' . $this->emailToVarchar($this->getCc());
        }
        if ($this->getBcc()) {
            $orderEmailRecord->To .= ', BCC: ' . $this->emailToVarchar($this->getBcc());
        }
        //always set result to try if
        $orderEmailRecord->Subject = $this->getSubject();
        if (Director::isDev()) {
            $orderEmailRecord->Subject .= _t('OrderEmail.FAKELY_RECORDED_AS_SENT', ' - FAKELY RECORDED AS SENT ');
        }
        $orderEmailRecord->Content = (string) $this->getHtmlBody();
        $orderEmailRecord->Result = true;
        $orderEmailRecord->OrderID = $this->order->ID;
        $orderEmailRecord->OrderStepID = $this->order->StatusID;
        $sendAllEmailsTo = Config::inst()->get(Email::class, 'send_all_emails_to');
        if ($sendAllEmailsTo) {
            $orderEmailRecord->To .=
            _t('OrderEmail.ACTUALLY_SENT_TO', ' | actually sent to: ')
            . $sendAllEmailsTo
            . _t('OrderEmail.CONFIG_EXPLANATION', ' - (Email::send_all_emails_to)');
        }
        $orderEmailRecord->write();

        return $orderEmailRecord;
    }
}
