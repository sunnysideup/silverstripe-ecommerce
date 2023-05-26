<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Group;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
use Sunnysideup\PermissionProvider\Interfaces\PermissionProviderFactoryProvider;

/**
 * @author: Nicolaas [at] Sunny Side Up .co.nz
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
        'CMS_ACCESS_StoreAdmin',
    ];

    public static function permission_provider_factory_runner(): Group
    {
        $gorupCode = EcommerceConfig::get(EcommerceRoleCustomer::class, 'customer_group_code');
        $group = Group::get()->filter(['Code' => $gorupCode])->first();
        if (! $group) {
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

        return $group;
    }
}
