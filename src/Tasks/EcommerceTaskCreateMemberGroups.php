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

 **/
class EcommerceTaskCreateMemberGroups extends BuildTask
{
    protected $title = 'Create e-commerce Member Groups';

    protected $description = 'Create the member groups and members for e-commerce, such as Customers and Shop Admininistrators.';

    public function run($request)
    {
        $permissionProviderFactory = PermissionProviderFactory::inst();
        DB::alteration_message('========================== <br />creating customer group', 'created');
        $permissionProviderFactory->setCode(
            EcommerceConfig::get(EcommerceRole::class, 'customer_group_code')
        )->setName(
            EcommerceConfig::get(EcommerceRole::class, 'customer_group_name')
        )->setPermissionCode(
            EcommerceConfig::get(EcommerceRole::class, 'customer_permission_code')
        )->CreateGroup(
            $member = null
        );

        DB::alteration_message('========================== <br />creating sales manager', 'created');
        //work out email
        $email = EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_email');
        if (! $email) {
            $email = 'websales@' . $_SERVER['HTTP_HOST'];
        }
        $firstName = EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_first_name');
        if (! $firstName) {
            $firstName = 'Web';
        }
        $surname = EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_surname');
        if (! $surname) {
            $surname = 'Sales';
        }

        $permissionProviderFactory->setEmail(
            $email
        )->setFirstName(
            $firstName
        )->setSurname(
            $surname
        )->CreateDefaultMember();

        DB::alteration_message('================================<br />creating shop admin group ', 'created');
        $permissionProviderFactory->setCode(
            EcommerceConfig::get(EcommerceRole::class, 'admin_group_code')
        )->setName(
            EcommerceConfig::get(EcommerceRole::class, 'admin_group_name')
        )->setPermissionCode(
            EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code')
        )->setRoleTitle(
            EcommerceConfig::get(EcommerceRole::class, 'admin_role_title')
        )->setPermissionArray(
            EcommerceConfig::get(EcommerceRole::class, 'admin_role_permission_codes')
        )->CreateGroup(
            $member = null
        );

        DB::alteration_message('========================== <br />creating default shop assistant member', 'created');
        //work out email
        $email = EcommerceConfig::get(EcommerceRole::class, 'assistant_group_user_email');
        if (! $email) {
            $email = 'assistant@' . $_SERVER['HTTP_HOST'];
        }
        $firstName = EcommerceConfig::get(EcommerceRole::class, 'assistant_group_user_first_name');
        if (! $firstName) {
            $firstName = 'Web';
        }
        $surname = EcommerceConfig::get(EcommerceRole::class, 'assistant_group_user_surname');
        if (! $surname) {
            $surname = 'Asssistant';
        }

        $permissionProviderFactory->setEmail(
            $email
        )->setFirstName(
            $firstName
        )->setSurname(
            $surname
        )->CreateDefaultMember();

        
        DB::alteration_message('================================<br />creating shop assistant group ', 'created');
        $permissionProviderFactory->setCode(
            EcommerceConfig::get(EcommerceRole::class, 'assistant_group_code')
        )->setName(
            EcommerceConfig::get(EcommerceRole::class, 'assistant_group_name')
        )->setPermissionCode(
            EcommerceConfig::get(EcommerceRole::class, 'assistant_permission_code')
        )->setRoleTitle(
            EcommerceConfig::get(EcommerceRole::class, 'assistant_role_title')
        )->setPermissionArray(
            EcommerceConfig::get(EcommerceRole::class, 'assistant_role_permission_codes')
        )->CreateGroup(
            $member = null
        );
    }
}
