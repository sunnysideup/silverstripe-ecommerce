<?php

namespace Sunnysideup\Ecommerce\Model\Process;

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
 * @method \Sunnysideup\Ecommerce\Model\Order Order()
 */
class Referral extends DataObject implements EditableEcommerceObject
{
    use OrderCached;

    public static function add_referral(Order $order, ?array $params): ?int
    {
        if(!empty($params)) {
            $filter = [
                'OrderID' => $order->ID,
            ];
            $ref = DataObject::get_one(Referral::class, $filter);
            if(!$ref) {
                $ref = Referral::create($filter);
            }
            $ref->Source = '';
            $ref->Source .= isset($params['fbclid']) ? 'Facebook Ads | ' . $params['fbclid'] : '';
            $ref->Source .= isset($params['gad']) ? 'Google Ads | ' . $params['gad'] : '';
            $ref->Source .= isset($params['twclid']) ? 'Twitter Ads | ' . $params['gad'] : '';
            $ref->Source .= $params['utm_source'] ?? '';

            $ref->Medium =  '';
            $ref->Medium .= isset($params['gclsrc']) ? 'Google Source | ' . $params['gclsrc'] : '';
            $ref->Medium .= $params['utm_medium'] ?? '';

            $ref->Campaign = '';
            $ref->Campaign .= isset($params['gclid']) ? 'Google Campaign | ' . $params['gclid'] : '';
            $ref->Campaign .= $params['utm_campaign'] ?? '';
            $ref->Campaign .= '|' . $params['utm_term'] ?? '';
            $ref->Campaign .= '|' . $params['utm_content'] ?? '';

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
        'Source' => 'Varchar(100)',
        'Medium' => 'Varchar(100)',
        'Campaign' => 'Varchar(100)',
        'IsSubmitted' => 'Boolean',
        'AmountInvoiced' => 'Currency',
        'AmountPaid' => 'Currency',
    ];

    private static $field_labels_right = [
        'Source' => 'Identifies the source of the traffic (e.g., google, newsletter)',
        'Medium' => 'The medium used to share the link (e.g., email, cpc)',
        'Campaign' => 'The specific campaign or promotion (e.g., spring_sale',
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
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Order.Title' => 'Order',
        'Order.Total' => 'Total',
        'Created' => 'When',
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

    public function getFrom(): string
    {
        $txt = $this->getFullCode();
        if (strpos($txt, 'Google Ads') !== false || strpos($txt, 'Google Source') !== false || strpos($txt, 'Google Campaign') !== false) {
            $medium = 'Google';
        } elseif (strpos($txt, 'Facebook Ads') !== false) {
            $medium = 'Facebook';
        } elseif (strpos($txt, 'Twitter Ads') !== false) {
            $medium = 'Twitter';
        } else {
            $medium = 'Other';
        }
        return $medium;
    }

    public function getFullCode(): string
    {
        return implode('|', array_filter([$this->Source,  $this->Medium , $this->Campaign]));
    }

}
