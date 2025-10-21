<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use DateTimeImmutable;
use DateTimeZone;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\CmsEditLinkField\Forms\Fields\CMSEditLinkField;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Traits\OrderCached;

// Class used to describe the steps in the checkout

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderFeedback
 *
 * @property string $Rating
 * @property string $Note
 * @property bool $Actioned
 * @property int $OrderID
 * @method \Sunnysideup\Ecommerce\Model\Order Order()
 */
class Referral extends DataObject implements EditableEcommerceObject
{
    use OrderCached;

    private static array $referral_sources = [
        // UTM
        'utm_source'   => ['Name' => 'Google Tracked', 'Field' => 'Source'],
        'utm_medium'   => ['Name' => 'Google Tracked', 'Field' => 'Medium'],
        'utm_campaign' => ['Name' => 'Google Tracked', 'Field' => 'Campaign'],
        'utm_term'     => ['Name' => 'Google Tracked', 'Field' => 'Term'],
        'utm_content'  => ['Name' => 'Google Tracked', 'Field' => 'Content'],

        // Google Ads / Analytics
        'gclid'   => ['Name' => 'Google Ads', 'Field' => 'Campaign'],
        'gclsrc'  => ['Name' => 'Google Source', 'Field' => 'Source'],
        'gad'     => ['Name' => 'Google Campaign', 'Field' => 'Campaign'],
        'gbraid'  => ['Name' => 'Google Ads', 'Field' => 'Campaign'],
        'wbraid'  => ['Name' => 'Google Ads', 'Field' => 'Campaign'],
        'dclid'   => ['Name' => 'Google Display', 'Field' => 'Campaign'],
        'srsltid' => ['Name' => 'Google Merchant', 'Field' => 'Campaign'],

        // Facebook / Meta
        'fbclid'     => ['Name' => 'Facebook Ads', 'Field' => 'Campaign'],
        'fb_clickid' => ['Name' => 'Facebook Ads', 'Field' => 'Campaign'],
        'fbc'        => ['Name' => 'Facebook Ads', 'Field' => 'Campaign'],
        'fbp'        => ['Name' => 'Facebook Ads', 'Field' => 'Source'],

        // Microsoft / Bing
        'msclkid' => ['Name' => 'Microsoft Ads', 'Field' => 'Campaign'],

        // TikTok
        'ttclid' => ['Name' => 'TikTok Ads', 'Field' => 'Campaign'],

        // Twitter / X
        'twclid' => ['Name' => 'Twitter Ads', 'Field' => 'Campaign'],

        // LinkedIn
        'li_fat_id' => ['Name' => 'LinkedIn Ads', 'Field' => 'Campaign'],

        // Pinterest
        'epik' => ['Name' => 'Pinterest Ads', 'Field' => 'Campaign'],

        // Snapchat
        'sccid' => ['Name' => 'Snapchat Ads', 'Field' => 'Campaign'],

        // Campaign Monitor
        'cm_mc_uid' => ['Name' => 'Campaign Monitor', 'Field' => 'Source'],
        'cm_mc_mid' => ['Name' => 'Campaign Monitor', 'Field' => 'Campaign'],

        // Mailchimp
        'mc_cid' => ['Name' => 'Mailchimp', 'Field' => 'Campaign'],
        'mc_eid' => ['Name' => 'Mailchimp', 'Field' => 'Source'],
        'mc_tc'  => ['Name' => 'Mailchimp', 'Field' => 'Campaign'],
        'mc_id'  => ['Name' => 'Mailchimp', 'Field' => 'Campaign'],

        // Generic / Affiliate / Referral
        'ref'            => ['Name' => 'Affiliate Marketing', 'Field' => 'Source'],
        'rf'             => ['Name' => 'Affiliate Marketing', 'Field' => 'Source'],
        'referrer'       => ['Name' => 'Referral', 'Field' => 'Source'],
        'referral'       => ['Name' => 'Referral', 'Field' => 'Source'],
        'referral_code'  => ['Name' => 'Referral', 'Field' => 'Source'],
        'affid'          => ['Name' => 'Affiliate Marketing', 'Field' => 'Source'],
        'affsource'      => ['Name' => 'Affiliate Marketing', 'Field' => 'Source'],
        'aff_sub'        => ['Name' => 'Affiliate Marketing', 'Field' => 'Campaign'],
        'aff_sub2'       => ['Name' => 'Affiliate Marketing', 'Field' => 'Campaign'],
        'aff_sub3'       => ['Name' => 'Affiliate Marketing', 'Field' => 'Campaign'],
        'aff_sub4'       => ['Name' => 'Affiliate Marketing', 'Field' => 'Campaign'],
        'aff_sub5'       => ['Name' => 'Affiliate Marketing', 'Field' => 'Campaign'],
        'subid'          => ['Name' => 'Affiliate Marketing', 'Field' => 'Campaign'],
        'sub_id'         => ['Name' => 'Affiliate Marketing', 'Field' => 'Campaign'],
        'partner'        => ['Name' => 'Partner Marketing', 'Field' => 'Source'],
        'partnerid'      => ['Name' => 'Partner Marketing', 'Field' => 'Source'],
        'cid'            => ['Name' => 'Campaign', 'Field' => 'Campaign'],
        'campaignid'     => ['Name' => 'Campaign', 'Field' => 'Campaign'],
        'adid'           => ['Name' => 'Advertising', 'Field' => 'Campaign'],
        'creative'       => ['Name' => 'Advertising', 'Field' => 'Content'],
        'clickid'        => ['Name' => 'Advertising', 'Field' => 'Campaign'],

        // Channel grouping
        'organic' => ['Name' => 'Organic Search', 'Field' => 'Medium'],
        'direct'  => ['Name' => 'Direct Traffic', 'Field' => 'Medium'],
        'social'  => ['Name' => 'Social Media', 'Field' => 'Medium'],
        'email'   => ['Name' => 'Email Marketing', 'Field' => 'Medium'],
        'push'    => ['Name' => 'Push Notifications', 'Field' => 'Medium'],
        'other'   => ['Name' => 'Other Referral Source', 'Field' => 'Medium'],

        // Custom
    ];


    private static $basic_searches = [
        'google'      => ['Name' => 'Google Other', 'Field' => 'From'],
        'facebook'    => ['Name' => 'Facebook Other', 'Field' => 'From'],
        'chatgpt.com' => ['Name' => 'ChatGPT Other', 'Field' => 'From'],
    ];

    public static function add_referral(Order $order, ?array $params = []): ?int
    {
        if (!empty($params) && count($params) > 0) {
            $filter = [
                'UniqueID' => ($params['_uniqueID'] ?? '') ?: $order->ID,
            ];
            $ref = Referral::get()->filter($filter)->first();
            if (!$ref) {
                $ref = Referral::create($filter);
            }
            $params = Convert::raw2sql($params);
            $ref->LandingUrl = $params['_landingUrl'] ?? '';
            $ref->Referrer = $params['_referrer'] ?? '';
            if (!empty($params['_capturedAt'])) {
                $date = new DateTimeImmutable($params['_capturedAt'], new DateTimeZone('UTC'));
                $localDate = $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                $ref->CapturedAt = $localDate->format('Y-m-d H:i:s');
            }
            // set fields based on config
            $fieldValues = [];
            $from = [];
            $list = Config::inst()->get(Referral::class, 'referral_sources');
            unset($params['_uniqueID'], $params['_landingUrl'], $params['_referrer'], $params['_capturedAt']);
            foreach ($params as $key => $val) {
                $getVarDetails = $list[$key] ?? null;
                $name = $getVarDetails['Name'] ?? $key;
                $field = $getVarDetails['Field'] ?? '';
                $from[] = $name;
                if (!$field) {
                    $field = 'Source';
                    $val .= ' (' . $name . ')';
                }
                if (!isset($fieldValues[$field])) {
                    $fieldValues[$field] = [];
                }
                $fieldValues[$field][] = $val;
            }
            foreach ($fieldValues as $field => $values) {
                $ref->$field = implode(' | ', array_filter(array_unique($values)));
            }
            $ref->From = $from ? implode(' | ', array_filter(array_unique($from))) : 'Other';
            $ref->write();
            return $ref->ID;
        }
        return null;
    }

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $table_name = 'Referral';

    private static $db = [
        'UniqueID' => 'Varchar(36)',
        'From' => 'Varchar(100)',
        'Source' => 'Varchar(100)',
        'Medium' => 'Varchar(100)',
        'Campaign' => 'Varchar(100)',
        'Term' => 'Varchar(100)',
        'Content' => 'Varchar(100)',
        'IsSubmitted' => 'Boolean',
        'AmountInvoiced' => 'Currency',
        'Processed' => 'Boolean',
        'LandingUrl' => 'Varchar(255)',
        'Referrer' => 'Varchar(255)',
        'CapturedAt' => 'Datetime',
    ];

    private static $indexes = [
        'UniqueID' => [
            'type' => 'unique',
            'columns' => ['UniqueID'],
        ],
        'Source' => true,
        'Medium' => true,
        'Campaign' => true,
        'IsSubmitted' => true,
        'Processed' => true,
        'Created' => true,
        'Term' => true,
        'Content' => true,
        'Referrer' => true,
    ];

    private static $field_labels_right = [
        'Source' => 'Identifies the source of the traffic (e.g., google, newsletter)',
        'Medium' => 'The medium used to share the link (e.g., email, cpc)',
        'Campaign' => 'The specific campaign or promotion (e.g., spring_sale)',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_one = [
        'Order' => Order::class,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'Source' => 'PartialMatchFilter',
        'OrderID' => [
            'field' => NumericField::class,
            'title' => 'Order Number',
        ],
        'Medium' => 'PartialMatchFilter',
        'Campaign' => 'PartialMatchFilter',
        'IsSubmitted' => 'ExactMatchFilter',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Created' => 'When',
        'Order.Title' => 'Order',
        'IsSubmitted.NiceAndColourfull' => 'Submitted',
        'AmountInvoiced' => 'Invoice Amount',
        'From' => 'From',
        'Source' => 'Source',
        'Medium' => 'Medium',
        'Campaign' => 'Campaign',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'Title' => 'Varchar',
        'From' => 'Varchar',
        'FullCode' => 'Varchar',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $default_sort = [
        'ID' => 'DESC',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Referral';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Referrals';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'Customer Order Referrals';

    /**
     * standard SS variable.
     *
     * @return bool
     */
    private static $can_create = false;

    public function i18n_singular_name()
    {
        return _t('OrderFeedback.SINGULAR_NAME', 'Order Referral');
    }

    public function i18n_plural_name()
    {
        return _t('OrderFeedback.PLURAL_NAME', 'Order Referrals');
    }

    /**
     * these are only created programmatically
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canView($member);
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField(
            'OrderID',
            CMSEditLinkField::create(
                'OrderID',
                Injector::inst()->get(Order::class)->singular_name(),
                $this->getOrderCached()
            )
        );

        return $fields;
    }

    /**
     * link to edit the record.
     *
     * @param null|string $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function Title()
    {
        return $this->getTitle();
    }

    public function getTitle()
    {
        $string = $this->Created;
        $order = $this->getOrderCached();
        if ($order) {
            $string .= ' (' . $order->getTitle() . ')';
        }
        $string .= ' - ' . $this->Source;

        return $string;
    }

    public function getFromAfterwards(): string
    {
        if ($this->From) {
            return $this->From;
        } else {
            $source = Config::inst()->get(Referral::class, 'basic_searches');
            $txt = $this->getFullCode();
            $list = [];
            foreach ($source as $key => $getVarDetails) {
                if (strpos($txt, $key) !== false) {
                    $list[] = $getVarDetails['Name'];
                }
            }
        }
        return $list ? implode(' | ', array_filter(array_unique($list))) : 'Other';
    }

    public function getFullCode(): string
    {
        return implode('|', array_filter([$this->Source,  $this->Medium, $this->Campaign, $this->Term, $this->Content]));
    }


    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    public function ProcessReferral(?int $daysAgo = 180): void
    {
        if ($this->Processed) {
            return;
        }
        $save = false;
        $processed = false;
        $stale = strtotime($this->Created) < strtotime('-' . $daysAgo . ' days') ? true : false;
        if ($stale) {
            // by now we should have an order so even if we dont have an order it should still be marked as processed
            $processed = true;
            $save = true;
        }
        $order = $this->getOrderCached();
        if ($order) {
            if (!$this->IsSubmitted) {
                $this->IsSubmitted = $order->getIsSubmitted();
                $save = true;
            }
            if ($this->IsSubmitted) {
                if (!$this->AmountInvoiced) {
                    $this->AmountInvoiced = $order->getTotal();
                    $save = true;
                    $processed = true;
                }
            }
        }
        if (!$this->From) {
            $this->From = $this->getFromAfterwards();
            $save = true;
        }
        if ($save) {
            $this->Processed = $processed;
            $this->write();
        }
    }

    public function IsStaleWithoutOrder(?int $daysAgo = 180): bool
    {
        $isStale = strtotime($this->Created) < strtotime('-' . $daysAgo . ' days') ? true : false;
        if ($isStale) {
            if (! $this->OrderID) {
                return true;
            }
            $order = $this->getOrderCached();
            if (!$order) {
                return true;
            }
        }
        return false;
    }
}
