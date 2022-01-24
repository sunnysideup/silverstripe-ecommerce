<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Group;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
use Sunnysideup\PermissionProvider\Interfaces\PermissionProviderFactoryProvider;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions
 */
class EcommerceRoleCustomer implements PermissionProviderFactoryProvider
{
    use Configurable;

    /**
     * @var string
     */
    private static $customer_group_code = 'shopcustomers';

    /**
     * @var string
     */
    private static $customer_group_name = 'Shop Customers';

    /**
     * @var string
     */
    private static $customer_permission_code = 'SHOPCUSTOMER';

    public static function permission_provider_factory_runner(): Group
    {
        return PermissionProviderFactory::inst()
            ->setParentGroup(EcommerceRole::get_category())

            ->setCode(EcommerceConfig::get(EcommerceRoleCustomer::class, 'customer_group_code'))
            ->setGroupName(EcommerceConfig::get(EcommerceRoleCustomer::class, 'customer_group_name'))
            ->setPermissionCode(EcommerceConfig::get(EcommerceRoleCustomer::class, 'customer_permission_code'))

            ->setDescription(
                _t(
                    'EcommerceRoleCustomer.CUSTOMERS_HELP',
                    'Customer Permissions (usually very little)'
                )
            )
            ->setSort(98)

            ->CreateGroup($member = null)
        ;
    }
}
