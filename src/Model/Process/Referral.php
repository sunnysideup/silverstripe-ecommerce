<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use Override;
use SilverStripe\Security\Member;
use SilverStripe\Forms\FieldList;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use SilverStripe\Core\Config\Config;
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
 * @method Order Order()
 */
class Referral extends DataObject implements EditableEcommerceObject
{
    use OrderCached;

    private static array $referral_sources = [
        // UTM
        'utm_source' => [
            'Name' => 'Google Tracked',
            'Field' => 'Source',
        ],
        'utm_medium' => [
            'Name' => 'Google Tracked',
            'Field' => 'Medium',
        ],
        'utm_campaign' => [
            'Name' => 'Google Tracked',
            'Field' => 'Campaign',
        ],
        'utm_term' => [
            'Name' => 'Google Tracked',
            'Field' => 'Term',
        ],
        'utm_content' => [
            'Name' => 'Google Tracked',
            'Field' => 'Content',
        ],

        // Google Ads / Analytics
        'gclid' => [
            'Name' => 'Google Ads',
            'Field' => 'Campaign',
        ],
        'gclsrc' => [
            'Name' => 'Google Source',
            'Field' => 'Source',
        ],
        'gad' => [
            'Name' => 'Google Campaign',
            'Field' => 'Campaign',
        ],
        'gbraid' => [
            'Name' => 'Google Ads',
            'Field' => 'Campaign',
        ],
        'wbraid' => [
            'Name' => 'Google Ads',
            'Field' => 'Campaign',
        ],
        'dclid' => [
            'Name' => 'Google Display',
            'Field' => 'Campaign',
        ],
        'srsltid' => [
            'Name' => 'Google Merchant',
            'Field' => 'Campaign',
        ],

        // Facebook / Meta
        'fbclid' => [
            'Name' => 'Facebook Ads',
            'Field' => 'Campaign',
        ],
        'fb_clickid' => [
            'Name' => 'Facebook Ads',
            'Field' => 'Campaign',
        ],
        'fbc' => [
            'Name' => 'Facebook Ads',
            'Field' => 'Campaign',
        ],
        'fbp' => [
            'Name' => 'Facebook Ads',
            'Field' => 'Source',
        ],

        // Microsoft / Bing
        'msclkid' => [
            'Name' => 'Microsoft Ads',
            'Field' => 'Campaign',
        ],

        // TikTok
        'ttclid' => [
            'Name' => 'TikTok Ads',
            'Field' => 'Campaign',
        ],

        // Twitter / X
        'twclid' => [
            'Name' => 'Twitter Ads',
            'Field' => 'Campaign',
        ],

        // LinkedIn
        'li_fat_id' => [
            'Name' => 'LinkedIn Ads',
            'Field' => 'Campaign',
        ],

        // Pinterest
        'epik' => [
            'Name' => 'Pinterest Ads',
            'Field' => 'Campaign',
        ],

        // Snapchat
        'sccid' => [
            'Name' => 'Snapchat Ads',
            'Field' => 'Campaign',
        ],

        // Campaign Monitor
        'cm_mc_uid' => [
            'Name' => 'Campaign Monitor',
            'Field' => 'Source',
        ],
        'cm_mc_mid' => [
            'Name' => 'Campaign Monitor',
            'Field' => 'Campaign',
        ],

        // Mailchimp
        'mc_cid' => [
            'Name' => 'Mailchimp',
            'Field' => 'Campaign',
        ],
        'mc_eid' => [
            'Name' => 'Mailchimp',
            'Field' => 'Source',
        ],
        'mc_tc' => [
            'Name' => 'Mailchimp',
            'Field' => 'Campaign',
        ],
        'mc_id' => [
            'Name' => 'Mailchimp',
            'Field' => 'Campaign',
        ],

        // Generic / Affiliate / Referral
        'ref' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Source',
        ],
        'rf' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Source',
        ],
        'referrer' => [
            'Name' => 'Referral',
            'Field' => 'Source',
        ],
        'referral' => [
            'Name' => 'Referral',
            'Field' => 'Source',
        ],
        'referral_code' => [
            'Name' => 'Referral',
            'Field' => 'Source',
        ],
        'affid' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Source',
        ],
        'affsource' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Source',
        ],
        'aff_sub' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Campaign',
        ],
        'aff_sub2' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Campaign',
        ],
        'aff_sub3' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Campaign',
        ],
        'aff_sub4' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Campaign',
        ],
        'aff_sub5' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Campaign',
        ],
        'subid' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Campaign',
        ],
        'sub_id' => [
            'Name' => 'Affiliate Marketing',
            'Field' => 'Campaign',
        ],
        'partner' => [
            'Name' => 'Partner Marketing',
            'Field' => 'Source',
        ],
        'partnerid' => [
            'Name' => 'Partner Marketing',
            'Field' => 'Source',
        ],
        'cid' => [
            'Name' => 'Campaign',
            'Field' => 'Campaign',
        ],
        'campaignid' => [
            'Name' => 'Campaign',
            'Field' => 'Campaign',
        ],
        'adid' => [
            'Name' => 'Advertising',
            'Field' => 'Campaign',
        ],
        'creative' => [
            'Name' => 'Advertising',
            'Field' => 'Content',
        ],
        'clickid' => [
            'Name' => 'Advertising',
            'Field' => 'Campaign',
        ],

        // Channel grouping
        'organic' => [
            'Name' => 'Organic Search',
            'Field' => 'Medium',
        ],
        'direct' => [
            'Name' => 'Direct Traffic',
            'Field' => 'Medium',
        ],
        'social' => [
            'Name' => 'Social Media',
            'Field' => 'Medium',
        ],
        'email' => [
            'Name' => 'Email Marketing',
            'Field' => 'Medium',
        ],
        'push' => [
            'Name' => 'Push Notifications',
            'Field' => 'Medium',
        ],
        'other' => [
            'Name' => 'Other Referral Source',
            'Field' => 'Medium',
        ],

        // Custom
    ];

    private static $basic_searches = [
        'google' => [
            'Name' => 'Google Other',
            'Field' => 'From',
        ],
        'facebook' => [
            'Name' => 'Facebook Other',
            'Field' => 'From',
        ],
        'chatgpt.com' => [
            'Name' => 'ChatGPT Other',
            'Field' => 'From',
        ],
    ];

    /**
     * The maximum number of referrals that can be recorded for one order.
     * This stops anonymous visitors from creating an unlimited number of rows.
     */
    private static int $max_referrals_per_order = 5;

    /**
     * The maximum number of (extra) parameters that are looked at in one request.
     */
    private static int $max_params_per_referral = 40;

    /**
     * Records a referral for the order.
     *
     * SECURITY: all of the data comes from the browser and must not be trusted:
     * - a referral that already belongs to another order is never changed
     * - only a limited number of referrals can be created per order
     * - values are cleaned (scalar only) and cut to the length of the database field.
     *
     * @return null|int the ID of the referral (also if it was already recorded), NULL if nothing was recorded.
     */
    public static function add_referral(Order $order, ?array $params = []): ?int
    {
        if ($params === null || $params === []) {
            return null;
        }
        if (! $order->exists()) {
            return null;
        }
        // 1. the unique ID: only accept sensible values (uuid or number as created by UTMReferralPointer.js)
        $uniqueID = $params['_uniqueID'] ?? '';
        if (! is_string($uniqueID) || ! preg_match('/^[A-Za-z0-9-]{8,36}$/', $uniqueID)) {
            $uniqueID = (string) $order->ID;
        }

        // 2. does it exist already?
        $ref = Referral::get()->filter(['UniqueID' => $uniqueID])->first();
        if ($ref) {
            if ((int) $ref->OrderID !== (int) $order->ID && (int) $ref->OrderID !== 0) {
                // belongs to another order: leave it alone.
                return (int) $ref->ID;
            }
        } else {
            // 3. limit the number of referrals per order.
            $existing = Referral::get()->filter(['OrderID' => $order->ID]);
            if ($existing->count() >= (int) Config::inst()->get(Referral::class, 'max_referrals_per_order')) {
                $last = $existing->sort('ID', 'DESC')->first();

                return $last ? (int) $last->ID : null;
            }
            $ref = Referral::create(['UniqueID' => $uniqueID]);
        }
        $ref->OrderID = $order->ID;

        $ref->LandingUrl = self::clean_value($params['_landingUrl'] ?? '', 255);
        $ref->Referrer = self::clean_value($params['_referrer'] ?? '', 255);
        $capturedAt = self::clean_value($params['_capturedAt'] ?? '', 40);
        if ($capturedAt !== '') {
            try {
                $date = new DateTimeImmutable($capturedAt, new DateTimeZone('UTC'));
                $localDate = $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
                $ref->CapturedAt = $localDate->format('Y-m-d H:i:s');
            } catch (Exception) {
                // invalid date: ignore.
            }
        }
        // set fields based on config
        $fieldValues = [];
        $from = [];
        $list = Config::inst()->get(Referral::class, 'referral_sources');
        unset($params['_uniqueID'], $params['_landingUrl'], $params['_referrer'], $params['_capturedAt']);
        $params = array_slice($params, 0, (int) Config::inst()->get(Referral::class, 'max_params_per_referral'), true);
        foreach ($params as $key => $val) {
            $key = self::clean_value($key, 50);
            $val = self::clean_value($val, 80);
            if ($key === '' || $val === '') {
                continue;
            }
            $getVarDetails = $list[$key] ?? null;
            $name = $getVarDetails['Name'] ?? $key;
            $field = $getVarDetails['Field'] ?? '';
            $from[] = $name;
            if (! $field) {
                $field = 'Source';
                $val .= ' (' . $name . ')';
            }
            if (! isset($fieldValues[$field])) {
                $fieldValues[$field] = [];
            }
            $fieldValues[$field][] = $val;
        }
        foreach ($fieldValues as $field => $values) {
            $ref->$field = self::clean_value(implode(' | ', array_filter(array_unique($values))), 100);
        }
        $ref->From = self::clean_value($from !== [] ? implode(' | ', array_filter(array_unique($from))) : 'Other', 100);
        $ref->write();

        return (int) $ref->ID;
    }

    /**
     * Cleans a value that came from the browser: scalar only, no tags, no control characters, limited length.
     * (no need for Convert::raw2sql as the ORM takes care of that.)
     */
    protected static function clean_value($value, int $maxLength): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $value = strip_tags((string) $value);
        $value = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return mb_substr(trim($value), 0, $maxLength);
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

    #[Override]
    public function i18n_singular_name()
    {
        return _t('OrderFeedback.SINGULAR_NAME', 'Order Referral');
    }

    #[Override]
    public function plural_name()
    {
        return _t('OrderFeedback.PLURAL_NAME', 'Order Referrals');
    }

    /**
     * these are only created programmatically
     * standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
    public function canView($member = null, $context = [])
    {
        if (! $member) {
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
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    #[Override]
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @return FieldList
     */
    #[Override]
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
    #[Override]
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

    #[Override]
    public function getTitle()
    {
        $string = $this->Created;
        $order = $this->getOrderCached();
        if ($order instanceof Order) {
            $string .= ' (' . $order->getTitle() . ')';
        }

        return $string . (' - ' . $this->Source);
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
                if (str_contains($txt, (string) $key)) {
                    $list[] = $getVarDetails['Name'];
                }
            }
        }

        return $list !== [] ? implode(' | ', array_filter(array_unique($list))) : 'Other';
    }

    public function getFullCode(): string
    {
        return implode('|', array_filter([$this->Source,  $this->Medium, $this->Campaign, $this->Term, $this->Content]));
    }

    #[Override]
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    public function ProcessReferral(?int $daysAgo = 365): void
    {
        if ($this->Processed) {
            return;
        }

        $save = false;
        $processed = false;
        $stale = strtotime($this->Created) < strtotime('-' . $daysAgo . ' days');
        if ($stale) {
            // by now we should have an order so even if we dont have an order it should still be marked as processed
            $processed = true;
            $save = true;
        }

        $order = $this->getOrderCached();
        if ($order instanceof Order) {
            if (! $this->IsSubmitted) {
                $this->IsSubmitted = $order->getIsSubmitted();
                $save = true;
            }

            if ($this->IsSubmitted && ! $this->AmountInvoiced) {
                $this->AmountInvoiced = $order->getTotal();
                $save = true;
                $processed = true;
            }
        }

        if (! $this->From) {
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
        $isStale = strtotime($this->Created) < strtotime('-' . $daysAgo . ' days');
        if ($isStale) {
            if (! $this->OrderID) {
                return true;
            }

            $amountInvoiced = floatval($this->AmountInvoiced);
            if ($amountInvoiced <= 0.01) {
                return true;
            }
        }

        return false;
    }
}
