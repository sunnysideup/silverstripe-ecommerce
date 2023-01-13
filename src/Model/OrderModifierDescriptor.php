<?php

namespace Sunnysideup\Ecommerce\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

class OrderModifierDescriptor extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $table_name = 'OrderModifierDescriptor';

    private static $db = [
        'ModifierClassName' => 'DBClassName(\'Sunnysideup\\Ecommerce\\Model\\OrderModifier\')',
        'Heading' => 'Varchar',
        'Description' => 'Text',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_one = [
        'Link' => SiteTree::class,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $indexes = [
        'ModifierClassName' => true,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'Heading' => 'PartialMatchFilter',
        'Description' => 'PartialMatchFilter',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $field_labels = [
        'ModifierClassName' => 'Code',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'RealName' => 'Code',
        'Heading' => 'Heading',
        'Description' => 'Description',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'RealName' => 'Varchar',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Modifier Description';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Modifier Descriptions';

    public function i18n_singular_name()
    {
        return _t('OrderModifier.ORDEREXTRADESCRIPTION', 'Order Modifier Description');
    }

    public function i18n_plural_name()
    {
        return _t('OrderModifier.ORDEREXTRADESCRIPTIONS', 'Order Modifier Descriptions');
    }

    /**
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
     *
     * @return bool
     */
    public function canEdit($member = null)
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

        return parent::canEdit($member);
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
        $fields->replaceField('ModifierClassName', new ReadonlyField('RealName', 'Name'));
        $fields->replaceField('LinkID', new TreeDropdownField('LinkID', 'More info link (optional)', SiteTree::class));
        $fields->replaceField('Description', new TextareaField('Description', 'Description'));

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
        return 'error';
    }

    /**
     * casted Variable.
     *
     * @return string
     */
    public function RealName()
    {
        return $this->getRealName();
    }

    public function getRealName()
    {
        if (class_exists($this->ModifierClassName)) {
            $singleton = singleton($this->ModifierClassName);

            return $singleton->i18n_singular_name();
        }

        return $this->ModifierClassName;
    }

    /**
     * Adds OrderModifierDescriptors and deletes the irrelevant ones
     * stardard SS method.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $arrayOfModifiers = EcommerceConfig::get(Order::class, 'modifiers');
        if (! is_array($arrayOfModifiers)) {
            $arrayOfModifiers = [];
        }

        if ([] !== $arrayOfModifiers) {
            foreach ($arrayOfModifiers as $className) {
                $orderModifier_Descriptor = DataObject::get_one(
                    OrderModifierDescriptor::class,
                    ['ModifierClassName' => $className],
                    $cacheDataObjectGetOne = false
                );
                if (! $orderModifier_Descriptor) {
                    $modifier = Injector::inst()->get($className);
                    $orderModifier_Descriptor = OrderModifierDescriptor::create();
                    $orderModifier_Descriptor->ModifierClassName = $className;
                    $orderModifier_Descriptor->Heading = $modifier->i18n_singular_name();
                    $orderModifier_Descriptor->write();
                    DB::alteration_message('Creating description for ' . $className, 'created');
                }
            }
        }

        //delete the ones that are not relevant
        $orderModifierDescriptors = OrderModifierDescriptor::get();
        if ($orderModifierDescriptors->exists()) {
            foreach ($orderModifierDescriptors as $orderModifierDescriptor) {
                if (! in_array($orderModifierDescriptor->ModifierClassName, $arrayOfModifiers, true)) {
                    $orderModifierDescriptor->delete();
                    DB::alteration_message('Deleting description for ' . $orderModifierDescriptor->ModifierClassName, 'deleted');
                }
            }
        }
    }

    /**
     * stardard SS method.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (isset($_REQUEST['NoLinkForOrderModifierDescriptor']) && $_REQUEST['NoLinkForOrderModifierDescriptor']) {
            $this->LinkID = 0;
        }
    }
}
