<?php

namespace Sunnysideup\Ecommerce\Model\Process;

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

    private static $referral_sources = [
        'gid' => 'Google Ads',
        'utm_source' => 'Google Tracked',
        'utm_medium' => 'Google Tracked',
        'utm_campaign' => 'Google Tracked',
        'utm_term' => 'Google Tracked',
        'utm_content' => 'Google Tracked',
        'gclid' => 'Google Ads',
        'gclsrc' => 'Google Source',
        'gad' => 'Google Campaign',
        'fbclid' => 'Facebook Ads',
        'fb_clickid' => 'Facebook Ads',
        'twclid' => 'Twitter Ads',
        'rf' => 'Affiliate Marketing',
        'subid' => 'Affiliate Marketing',
        'referral_code' => 'Custom',
        'referrer' => 'Custom',
        'direct' => 'Direct Traffic',
        'organic' => 'Organic Search',
        'social' => 'Social Media',
        'email' => 'Email Marketing',
        'other' => 'Other Referral Source',
        'google' => 'Google Other',
        'facebook' => 'Facebook Other',
        'chatgpt.com' => 'ChatGPT Other',
    ];


    public static function add_referral(Order $order, ?array $params): ?int
    {
        if (!empty($params) && count($params) > 0) {
            $filter = [
                'OrderID' => $order->ID,
            ];
            $ref = DataObject::get_one(Referral::class, $filter);
            if (!$ref) {
                $ref = Referral::create($filter);
            }
            $params = Convert::raw2sql($params);
            $list = Config::inst()->get(Referral::class, 'referral_sources');
            $source = [];
            $from = [];
            foreach ($list as $getVar => $name) {
                if (isset($params[$getVar])) {
                    $val = $params[$getVar];
                    $from[] = $name;
                    switch ($getVar) {
                        case 'utm_source':
                            $source[] = $val;
                            break;
                        case 'utm_medium':
                            $ref->Medium = $val;
                            break;
                        case 'utm_campaign':
                            $ref->Campaign = $val;
                            break;
                        case 'utm_term':
                            $ref->Term = $val;
                            break;
                        case 'utm_content':
                            $ref->Content = $val;
                            break;
                        default:
                            $source[] = $val . ' (' . $getVar . ')';
                            break;
                    }
                }
            }
            $ref->Source = implode(' | ', array_filter(array_unique($source)));
            $ref->From = implode(' | ', array_filter(array_unique($from)));
            $ref->write();
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
        'From' => 'Varchar(100)',
        'Source' => 'Varchar(100)',
        'Medium' => 'Varchar(100)',
        'Campaign' => 'Varchar(100)',
        'Term' => 'Varchar(100)',
        'Content' => 'Varchar(100)',
        'IsSubmitted' => 'Boolean',
        'AmountInvoiced' => 'Currency',
        'AmountPaid' => 'Currency',
        'Processed' => 'Boolean',
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
        'AmountInvoiced' => 'Invoiced',
        'AmountPaid' => 'Paid',
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
            $txt = $this->getFullCode();
            $list = [];
            foreach (Config::inst()->get(Referral::class, 'referral_sources') as $key => $name) {
                if (strpos($txt, $key) !== false) {
                    $list[] = $name;
                }
            }
        }
        return $list ? implode(' | ', array_filter(array_unique($list))) : 'Other';
    }

    public function getFullCode(): string
    {
        return implode('|', array_filter([$this->Source,  $this->Medium, $this->Campaign]));
    }


    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    public function AttachData()
    {
        $change = $this->Processed ? false : true;
        $this->Processed = true;
        $order = $this->getOrderCached();
        if ($order) {
            if (!$this->IsSubmitted) {
                $this->IsSubmitted = $order->getIsSubmitted();
                if ($this->IsSubmitted) {
                    $change = true;
                }
            }
            if ($this->IsSubmitted) {
                if (!$this->AmountInvoiced) {
                    $this->AmountInvoiced = $order->getTotal();
                    if ($this->AmountInvoiced) {
                        $change = true;
                    }
                }
                if (!$this->AmountPaid) {
                    $this->AmountPaid = $order->getTotalPaid();
                    if ($this->AmountPaid) {
                        $change = true;
                    }
                }
            }
        }
        if (!$this->From) {
            $this->From = $this->getFromAfterwards();
            if ($this->From) {
                $change = true;
            }
        }
        if ($change) {
            $this->write();
        }
    }
}
