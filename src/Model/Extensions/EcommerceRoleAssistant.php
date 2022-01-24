<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Order;

use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

use SilverStripe\Core\Config\Configurable;
use Sunnysideup\PermissionProvider\Interfaces\PermissionProviderFactoryProvider;

use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions
 */
class EcommerceRoleAssistant implements PermissionProviderFactoryProvider
{

    use Configurable;


    /**
     * @var string
     */
    private static $assistant_group_code = 'shopassistants';

    /**
     * @var string
     */
    private static $assistant_group_name = 'Shop Assistants';

    /**
     * @var string
     */
    private static $assistant_group_user_first_name = 'Shop';

    /**
     * @var string
     */
    private static $assistant_group_user_surname = 'Assistants';

    /**
     * @var string
     */
    private static $assistant_group_user_email = 'shopassistants';

    /**
     * @var string
     */
    private static $assistant_permission_code = 'SHOPASSISTANTS';

    /**
     * @var string
     */
    private static $assistant_role_title = 'Shop Assistant';

    /**
     * @var array
     */
    private static $assistant_role_permission_codes = [
        'CMS_ACCESS_SalesAdmin',
        'CMS_ACCESS_SalesAdminByOrderSize',
        'CMS_ACCESS_SalesAdminByOrderStep',
        'CMS_ACCESS_SalesAdminByDeliveryOption',
        'CMS_ACCESS_SalesSalesAdminProcess',
        'CMS_ACCESS_SalesAdminByPaymentType',
        'CMS_ACCESS_StoreAdmin'
    ];



    public static function permission_provider_factory_runner() : Group
    {
        return PermissionProviderFactory::inst()
            ->setParentGroup(EcommerceRole::get_category())

            ->setEmail(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_group_user_email'))
            ->setFirstName(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_group_user_first_name'))
            ->setSurname(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_group_user_surname'))
            ->setCode(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_group_code'))
            ->setGroupName(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_group_name'))
            ->setPermissionCode(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_permission_code'))
            ->setRoleTitle(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_role_title'))
            ->setPermissionArray(EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_role_permission_codes'))

            ->setDescription(
                _t(
                    'EcommerceRoleAssistant.SHOP_ASSISTANTS_HELP',
                    'Shop Assistant - can only view sales details and makes notes about orders'
                )
            )
            ->setSort(100)

            ->CreateGroupAndMember()
        ;
    }

}
