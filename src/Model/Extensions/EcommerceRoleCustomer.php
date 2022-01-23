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

use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Order;

use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\PermissionProvider\Interfaces\PermissionProviderFactoryProvider;

use SilverStripe\Core\Config\Configurable;
/**
 *
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


    public static function permission_provider_factory_runner() : Group
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
