<?php


/**
 * create the e-commerce specific Member Groups.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskCreateMemberGroups extends BuildTask
{
    protected $title = 'Create e-commerce Member Groups';

    protected $description = 'Create the member groups and members for e-commerce, such as Customers and Shop Admininistrators.';

    public function run($request)
    {
        $permissionProviderFactory = Injector::inst()->get('PermissionProviderFactory');
        db::alteration_message('========================== <br />creating customer group', 'created');
        $permissionProviderFactory->CreateGroup(
            $code = EcommerceConfig::get('EcommerceRole', 'customer_group_code'),
            $name = EcommerceConfig::get('EcommerceRole', 'customer_group_name'),
            $parentGroup = null,
            $permissionCode = EcommerceConfig::get('EcommerceRole', 'customer_permission_code'),
            $roleTitle = '',
            $permissionArray = array(),
            $member = null
        );

        db::alteration_message('========================== <br />creating sales manager', 'created');
        //work out email
        $email = EcommerceConfig::get('EcommerceRole', 'admin_group_user_email');
        if (!$email) {
            $email = 'websales@'.$_SERVER['HTTP_HOST'];
        }
        $firstName = EcommerceConfig::get('EcommerceRole', 'admin_group_user_first_name');
        if (!$firstName) {
            $firstName = 'Web';
        }
        $surname = EcommerceConfig::get('EcommerceRole', 'admin_group_user_surname');
        if (!$surname) {
            $surname = 'Sales';
        }

        $member = $permissionProviderFactory->CreateDefaultMember(
            $email,
            $firstName,
            $surname
        );
        db::alteration_message('================================<br />creating shop admin group ', 'created');

        $permissionProviderFactory->CreateGroup(
            $code = EcommerceConfig::get('EcommerceRole', 'admin_group_code'),
            $name = EcommerceConfig::get('EcommerceRole', 'admin_group_name'),
            $parentGroup = null,
            $permissionCode = EcommerceConfig::get('EcommerceRole', 'admin_permission_code'),
            $roleTitle = EcommerceConfig::get('EcommerceRole', 'admin_role_title'),
            $permissionArray = EcommerceConfig::get('EcommerceRole', 'admin_role_permission_codes'),
            $member
        );

        //work out email
        $email = EcommerceConfig::get('EcommerceRole', 'assistant_group_user_email');
        if (!$email) {
            $email = 'assistant@'.$_SERVER['HTTP_HOST'];
        }
        $firstName = EcommerceConfig::get('EcommerceRole', 'assistant_group_user_first_name');
        if (!$firstName) {
            $firstName = 'Web';
        }
        $surname = EcommerceConfig::get('EcommerceRole', 'assistant_group_user_surname');
        if (!$surname) {
            $surname = 'Asssistant';
        }

        $member = $permissionProviderFactory->CreateDefaultMember(
            $email,
            $firstName,
            $surname
        );
        db::alteration_message('================================<br />creating shop assistant group ', 'created');

        $permissionProviderFactory->CreateGroup(
            $code = EcommerceConfig::get('EcommerceRole', 'assistant_group_code'),
            $name = EcommerceConfig::get('EcommerceRole', 'assistant_group_name'),
            $parentGroup = null,
            $permissionCode = EcommerceConfig::get('EcommerceRole', 'assistant_permission_code'),
            $roleTitle = EcommerceConfig::get('EcommerceRole', 'assistant_role_title'),
            $permissionArray = EcommerceConfig::get('EcommerceRole', 'assistant_role_permission_codes'),
            $member
        );
    }
}
