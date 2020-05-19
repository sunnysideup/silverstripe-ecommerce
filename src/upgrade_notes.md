2020-05-19 03:26

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 15:26:12] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Warning:  Declaration of Sunnysideup\Ecommerce\Filesystem\ProductImage::Thumbnail() should be compatible with SilverStripe\Assets\File::Thumbnail($width, $height) in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Filesystem/ProductImage.php on line 16
    [3] => PHP Warning:  Declaration of Sunnysideup\Ecommerce\Model\OrderAttribute::canCreate($member = NULL) should be compatible with SilverStripe\ORM\DataObject::canCreate($member = NULL, $context = Array) in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderAttribute.php on line 42
    [4] => PHP Warning:  Declaration of Sunnysideup\Ecommerce\Model\OrderModifierDescriptor::canCreate($member = NULL) should be compatible with SilverStripe\ORM\DataObject::canCreate($member = NULL, $context = Array) in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderModifierDescriptor.php on line 36
    [5] => PHP Warning:  Declaration of Sunnysideup\Ecommerce\Model\Order::canCreate($member = NULL) should be compatible with SilverStripe\ORM\DataObject::canCreate($member = NULL, $context = Array) in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/Order.php on line 176
    [6] => PHP Warning:  Declaration of Sunnysideup\Ecommerce\Forms\Fields\OrderStepField::setValue($value) should be compatible with SilverStripe\Forms\FormField::setValue($value, $data = NULL) in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Forms/Fields/OrderStepField.php on line 158
    [7] => PHP Warning:  Declaration of Sunnysideup\Ecommerce\Forms\Fields\BuyableSelectField::setValue($data) should be compatible with SilverStripe\Forms\FormField::setValue($value, $data = NULL) in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Forms/Fields/BuyableSelectField.php on line 176
    [8] => PHP Fatal error:  Cannot declare class Sunnysideup\Ecommerce\Tasks\EcommerceTaskLinkProductWithImages, because the name is already in use in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Tasks/EcommerceTaskLinkProductWithImages.php on line 177
)
