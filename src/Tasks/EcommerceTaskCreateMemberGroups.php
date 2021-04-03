<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;

/**
 * create the e-commerce specific Member Groups.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCreateMemberGroups extends BuildTask
{
    protected $title = 'Create e-commerce Member Groups';

    protected $description = 'Create the member groups and members for e-commerce, such as Customers and Shop Admininistrators.';

    public function run($request)
    {
        DB::alteration_message('========================== <br />creating customer group', 'created');
        PermissionProviderFactory::inst()
            ->setCode(EcommerceConfig::get(EcommerceRole::class, 'customer_group_code'))
            ->setGroupName(EcommerceConfig::get(EcommerceRole::class, 'customer_group_name'))
            ->setPermissionCode(EcommerceConfig::get(EcommerceRole::class, 'customer_permission_code'))
            ->CreateGroup($member = null)
        ;

        DB::alteration_message('========================== <br />creating sales manager', 'created');
        //work out email

        PermissionProviderFactory::inst()
            ->setEmail(EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_email'))
            ->setFirstName(EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_first_name'))
            ->setSurname(EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_surname'))
            ->setCode(EcommerceConfig::get(EcommerceRole::class, 'admin_group_code'))
            ->setGroupName(EcommerceConfig::get(EcommerceRole::class, 'admin_group_name'))
            ->setPermissionCode(EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code'))
            ->setRoleTitle(EcommerceConfig::get(EcommerceRole::class, 'admin_role_title'))
            ->setPermissionArray(EcommerceConfig::get(EcommerceRole::class, 'admin_role_permission_codes'))
            ->CreateGroupAndMember()
        ;

        DB::alteration_message('========================== <br />creating default shop assistant member', 'created');
        //work out email

        PermissionProviderFactory::inst()
            ->setEmail(EcommerceConfig::get(EcommerceRole::class, 'assistant_group_user_email'))
            ->setFirstName(EcommerceConfig::get(EcommerceRole::class, 'assistant_group_user_first_name'))
            ->setSurname(EcommerceConfig::get(EcommerceRole::class, 'assistant_group_user_surname'))
            ->setCode(EcommerceConfig::get(EcommerceRole::class, 'assistant_group_code'))
            ->setGroupName(EcommerceConfig::get(EcommerceRole::class, 'assistant_group_name'))
            ->setPermissionCode(EcommerceConfig::get(EcommerceRole::class, 'assistant_permission_code'))
            ->setRoleTitle(EcommerceConfig::get(EcommerceRole::class, 'assistant_role_title'))
            ->setPermissionArray(EcommerceConfig::get(EcommerceRole::class, 'assistant_role_permission_codes'))
            ->CreateGroupAndMember()
        ;
    }
}
