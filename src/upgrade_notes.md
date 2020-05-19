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

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 15:35:24] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => 
    [3] => In Broker.php line 215:
    [4] => 
    [5] =>   [PHPStan\Broker\ClassAutoloadingException]
    [6] =>   Class RestfulServer not found and could not be autoloaded.
    [7] => 
    [8] => 
    [9] => Exception trace:
    [10] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [11] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [12] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Api/EcommerceRestfulServer.php:49
    [13] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [14] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [15] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [16] =>  spl_autoload_call() at n/a:n/a
    [17] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [18] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [19] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [20] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [21] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [22] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [23] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [24] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [25] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [26] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [27] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [28] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [29] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [30] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [31] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [32] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [33] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [34] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [35] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [36] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [37] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [38] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [39] => 
    [40] => In Broker.php line 215:
    [41] => 
    [42] =>   [PHPStan\Broker\ClassAutoloadingException]
    [43] =>   Class mailer not found and could not be autoloaded.
    [44] => 
    [45] => 
    [46] => Exception trace:
    [47] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [48] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [49] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [50] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [51] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [52] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [53] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Api/EcommerceRestfulServer.php:49
    [54] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [55] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [56] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [57] =>  spl_autoload_call() at n/a:n/a
    [58] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [59] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [60] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [61] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [62] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [63] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [64] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [65] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [66] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [67] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [68] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [69] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [70] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [71] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [72] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [73] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [74] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [75] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [76] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [77] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [78] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [79] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [80] => 
    [81] => In Broker.php line 215:
    [82] => 
    [83] =>   [Error]
    [84] =>   Class 'PHPStan\Broker\ClassAutoloadingException' not found
    [85] => 
    [86] => 
    [87] => Exception trace:
    [88] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [89] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [90] =>  spl_autoload_call() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [91] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [92] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [93] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [94] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [95] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [96] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [97] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [98] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [99] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [100] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [101] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [102] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [103] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [104] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Api/EcommerceRestfulServer.php:49
    [105] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [106] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [107] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [108] =>  spl_autoload_call() at n/a:n/a
    [109] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [110] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [111] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [112] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [113] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [114] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [115] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [116] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [117] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [118] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [119] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [120] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [121] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [122] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [123] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [124] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [125] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [126] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [127] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [128] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [129] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [130] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [131] => 
    [132] => In OrderAttribute.php line 229:
    [133] => 
    [134] =>   [ParseError]
    [135] =>   syntax error, unexpected ')', expecting '('
    [136] => 
    [137] => 
    [138] => Exception trace:
    [139] =>   at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderAttribute.php:229
    [140] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [141] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [142] =>  spl_autoload_call() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [143] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [144] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [145] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [146] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [147] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [148] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [149] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [150] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [151] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [152] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [153] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [154] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [155] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [156] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Api/EcommerceRestfulServer.php:49
    [157] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [158] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [159] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [160] =>  spl_autoload_call() at n/a:n/a
    [161] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [162] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [163] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [164] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [165] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [166] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [167] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [168] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [169] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [170] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [171] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [172] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [173] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [174] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [175] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [176] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [177] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [178] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [179] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [180] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [181] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [182] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [183] => 
    [184] => In OrderAttribute.php line 229:
    [185] => 
    [186] =>   [ParseError]
    [187] =>   syntax error, unexpected ')', expecting '('
    [188] => 
    [189] => 
    [190] => Exception trace:
    [191] =>   at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderAttribute.php:229
    [192] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [193] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [194] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [195] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [196] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [197] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [198] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [199] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [200] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [201] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [202] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [203] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [204] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [205] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [206] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Api/EcommerceRestfulServer.php:49
    [207] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [208] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [209] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [210] =>  spl_autoload_call() at n/a:n/a
    [211] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [212] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [213] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [214] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [215] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [216] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [217] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [218] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [219] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [220] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [221] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [222] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [223] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [224] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [225] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [226] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [227] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [228] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [229] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [230] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [231] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [232] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [233] => 
    [234] => inspect [-d|--root-dir ROOT-DIR] [-w|--write] [--skip-visibility] [--] <path>
    [235] => 
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 15:39:44] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => 
    [3] => In Broker.php line 215:
    [4] => 
    [5] =>   [PHPStan\Broker\ClassAutoloadingException]
    [6] =>   Class mailer not found and could not be autoloaded.
    [7] => 
    [8] => 
    [9] => Exception trace:
    [10] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [11] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [12] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [13] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [14] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [15] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [16] =>  spl_autoload_call() at n/a:n/a
    [17] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [18] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [19] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [20] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [21] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [22] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [23] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [24] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [25] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [26] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [27] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [28] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [29] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [30] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [31] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [32] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [33] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [34] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [35] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [36] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [37] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [38] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [39] => 
    [40] => In Broker.php line 215:
    [41] => 
    [42] =>   [Error]
    [43] =>   Class 'PHPStan\Broker\ClassAutoloadingException' not found
    [44] => 
    [45] => 
    [46] => Exception trace:
    [47] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [48] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [49] =>  spl_autoload_call() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [50] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [51] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [52] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [53] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [54] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [55] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [56] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [57] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [58] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [59] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [60] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [61] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [62] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [63] =>  spl_autoload_call() at n/a:n/a
    [64] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [65] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [66] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [67] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [68] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [69] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [70] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [71] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [72] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [73] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [74] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [75] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [76] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [77] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [78] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [79] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [80] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [81] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [82] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [83] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [84] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [85] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [86] => 
    [87] => In OrderAttribute.php line 229:
    [88] => 
    [89] =>   [ParseError]
    [90] =>   syntax error, unexpected ')', expecting '('
    [91] => 
    [92] => 
    [93] => Exception trace:
    [94] =>   at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderAttribute.php:229
    [95] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [96] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [97] =>  spl_autoload_call() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [98] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [99] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [100] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [101] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [102] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [103] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [104] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [105] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [106] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [107] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [108] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [109] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [110] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [111] =>  spl_autoload_call() at n/a:n/a
    [112] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [113] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [114] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [115] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [116] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [117] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [118] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [119] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [120] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [121] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [122] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [123] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [124] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [125] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [126] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [127] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [128] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [129] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [130] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [131] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [132] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [133] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [134] => 
    [135] => In OrderAttribute.php line 229:
    [136] => 
    [137] =>   [ParseError]
    [138] =>   syntax error, unexpected ')', expecting '('
    [139] => 
    [140] => 
    [141] => Exception trace:
    [142] =>   at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderAttribute.php:229
    [143] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [144] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [145] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [146] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [147] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [148] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [149] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [150] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [151] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [152] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [153] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Email/EcommerceDummyMailer.php:10
    [154] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [155] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [156] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [157] =>  spl_autoload_call() at n/a:n/a
    [158] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [159] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [160] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [161] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [162] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [163] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [164] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [165] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [166] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [167] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [168] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [169] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [170] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [171] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [172] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [173] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [174] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [175] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [176] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [177] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [178] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [179] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [180] => 
    [181] => inspect [-d|--root-dir ROOT-DIR] [-w|--write] [--skip-visibility] [--] <path>
    [182] => 
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 15:52:18] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => 
    [3] => In Broker.php line 224:
    [4] => 
    [5] =>   [PHPStan\Broker\ClassAutoloadingException]
    [6] =>   Error (Class 'PHPStan\Broker\ClassAutoloadingException' not found) thrown while autoloading class Shoppingcart.
    [7] => 
    [8] => 
    [9] => Exception trace:
    [10] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:224
    [11] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [12] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [13] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [14] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [15] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [16] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [17] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [18] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [19] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [20] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [21] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [22] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [23] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [24] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [25] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [26] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [27] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [28] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [29] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [30] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [31] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [32] => 
    [33] => In Broker.php line 215:
    [34] => 
    [35] =>   [Error]
    [36] =>   Class 'PHPStan\Broker\ClassAutoloadingException' not found
    [37] => 
    [38] => 
    [39] => Exception trace:
    [40] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [41] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [42] =>  spl_autoload_call() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [43] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [44] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [45] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [46] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [47] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [48] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [49] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [50] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [51] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [52] =>  spl_autoload_call() at n/a:n/a
    [53] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [54] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [55] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [56] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [57] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [58] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [59] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [60] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [61] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [62] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [63] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [64] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [65] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [66] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [67] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [68] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [69] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [70] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [71] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [72] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [73] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [74] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [75] => 
    [76] => In OrderAttribute.php line 229:
    [77] => 
    [78] =>   [ParseError]
    [79] =>   syntax error, unexpected ')', expecting '('
    [80] => 
    [81] => 
    [82] => Exception trace:
    [83] =>   at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderAttribute.php:229
    [84] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [85] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [86] =>  spl_autoload_call() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:215
    [87] =>  PHPStan\Broker\Broker->PHPStan\Broker\{closure}() at n/a:n/a
    [88] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [89] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [90] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [91] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [92] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [93] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [94] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [95] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [96] =>  spl_autoload_call() at n/a:n/a
    [97] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [98] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [99] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [100] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [101] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [102] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [103] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [104] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [105] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [106] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [107] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [108] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [109] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [110] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [111] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [112] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [113] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [114] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [115] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [116] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [117] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [118] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [119] => 
    [120] => In OrderAttribute.php line 229:
    [121] => 
    [122] =>   [ParseError]
    [123] =>   syntax error, unexpected ')', expecting '('
    [124] => 
    [125] => 
    [126] => Exception trace:
    [127] =>   at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderAttribute.php:229
    [128] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [129] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [130] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/OrderItem.php:52
    [131] =>  include() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:444
    [132] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [133] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [134] =>  spl_autoload_call() at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/ProductOrderItem.php:12
    [135] =>  require_once() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:159
    [136] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadItem() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Autoload/CollectionAutoloader.php:100
    [137] =>  SilverStripe\Upgrader\Autoload\CollectionAutoloader->loadClass() at n/a:n/a
    [138] =>  spl_autoload_call() at n/a:n/a
    [139] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [140] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [141] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [142] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [143] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [144] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [145] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [146] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [147] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [148] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [149] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [150] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [151] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [152] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [153] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [154] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [155] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [156] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [157] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [158] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [159] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [160] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [161] => 
    [162] => inspect [-d|--root-dir ROOT-DIR] [-w|--write] [--skip-visibility] [--] <path>
    [163] => 
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 15:56:51] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => 
    [3] => In Broker.php line 224:
    [4] => 
    [5] =>   [PHPStan\Broker\ClassAutoloadingException]
    [6] =>   ParseError (syntax error, unexpected ')', expecting '(') thrown while autoloading class Sunnysideup\Ecommerce\Model\Order.
    [7] => 
    [8] => 
    [9] => Exception trace:
    [10] =>   at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:224
    [11] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [12] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [13] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [14] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [15] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [16] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [17] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [18] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [19] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [20] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [21] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [22] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [23] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [24] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [25] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [26] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [27] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [28] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [29] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [30] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [31] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [32] => 
    [33] => In Order.php line 2165:
    [34] => 
    [35] =>   [ParseError]
    [36] =>   syntax error, unexpected ')', expecting '('
    [37] => 
    [38] => 
    [39] => Exception trace:
    [40] =>   at /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/Order.php:2165
    [41] =>  Composer\Autoload\includeFile() at /var/www/ss3/upgrader/vendor/composer/ClassLoader.php:322
    [42] =>  Composer\Autoload\ClassLoader->loadClass() at n/a:n/a
    [43] =>  spl_autoload_call() at n/a:n/a
    [44] =>  class_exists() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Broker/Broker.php:220
    [45] =>  PHPStan\Broker\Broker->hasClass() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/ClassCaseSensitivityCheck.php:27
    [46] =>  PHPStan\Rules\ClassCaseSensitivityCheck->checkClassNames() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:117
    [47] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->checkClasses() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Rules/Namespaces/ExistingNamesInUseRule.php:62
    [48] =>  PHPStan\Rules\Namespaces\ExistingNamesInUseRule->processNode() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:80
    [49] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\{closure}() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:316
    [50] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [51] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:699
    [52] =>  PHPStan\Analyser\NodeScopeResolver->processNode() at /var/www/ss3/upgrader/vendor/phpstan/phpstan/src/Analyser/NodeScopeResolver.php:176
    [53] =>  PHPStan\Analyser\NodeScopeResolver->processNodes() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/Visitor/PHPStanScopeVisitor.php:82
    [54] =>  SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\PHPStanScopeVisitor->enterNode() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:159
    [55] =>  PhpParser\NodeTraverser->traverseArray() at /var/www/ss3/upgrader/vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php:85
    [56] =>  PhpParser\NodeTraverser->traverse() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/PHPUpgradeRule.php:28
    [57] =>  SilverStripe\Upgrader\UpgradeRule\PHP\PHPUpgradeRule->transformWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:88
    [58] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->mutateSourceWithVisitors() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/UpgradeRule/PHP/ApiChangeWarningsRule.php:60
    [59] =>  SilverStripe\Upgrader\UpgradeRule\PHP\ApiChangeWarningsRule->upgradeFile() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Upgrader.php:61
    [60] =>  SilverStripe\Upgrader\Upgrader->upgrade() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/src/Console/InspectCommand.php:88
    [61] =>  SilverStripe\Upgrader\Console\InspectCommand->execute() at /var/www/ss3/upgrader/vendor/symfony/console/Command/Command.php:255
    [62] =>  Symfony\Component\Console\Command\Command->run() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:1001
    [63] =>  Symfony\Component\Console\Application->doRunCommand() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:271
    [64] =>  Symfony\Component\Console\Application->doRun() at /var/www/ss3/upgrader/vendor/symfony/console/Application.php:147
    [65] =>  Symfony\Component\Console\Application->run() at /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code:55
    [66] => 
    [67] => inspect [-d|--root-dir ROOT-DIR] [-w|--write] [--skip-visibility] [--] <path>
    [68] => 
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 15:59:03] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Cannot declare class Sunnysideup\Ecommerce\Tasks\EcommerceTaskCartManipulationCurrent, because the name is already in use in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Tasks/EcommerceTaskCartManipulation.php on line 32
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:01:39] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Cannot declare class ProductGroup because the name is already in use in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Pages/ProductGroup.php on line 105
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:15:16] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Class 'ProductGroup_Controller' not found in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Pages/ProductGroupSearchPageController.php on line 9
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:18:29] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Class 'Sunnysideup\Ecommerce\Pages\Page' not found in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Pages/ProductGroup.php on line 107
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:19:18] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Class 'DBTextField' not found in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Forms/Fields/EcommerceCreditCardField.php on line 16
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:22:50] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Class 'PolymorphicForeignKey' not found in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/Fieldtypes/BuyableFieldType.php on line 26
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:23:48] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Interface 'CompositeDBField' not found in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/Fieldtypes/BuyableFieldType.php on line 26
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:25:38] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Interface 'Sunnysideup\Ecommerce\Model\Fieldtypes\DBComposite' not found in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/Fieldtypes/BuyableFieldType.php on line 26
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Array
(
    [0] => Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
    [1] => [2020-05-19 16:26:03] Applying ApiChangeWarningsRule to OrderConverter.php...
    [2] => PHP Fatal error:  Sunnysideup\Ecommerce\Model\Fieldtypes\BuyableFieldType cannot implement SilverStripe\ORM\FieldType\DBComposite - it is not an interface in /var/www/ss3/upgrades/ecommerce-root/ecommerce/src/Model/Fieldtypes/BuyableFieldType.php on line 26
)

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/ecommerce/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Writing changes for 6 files
Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/ecommerce/src"
[2020-05-19 16:28:14] Applying ApiChangeWarningsRule to OrderConverter.php...
[2020-05-19 16:28:14] Applying UpdateVisibilityRule to OrderConverter.php...
[2020-05-19 16:28:14] Applying ApiChangeWarningsRule to EcommerceCountryVisitorCountryProvider.php...
[2020-05-19 16:28:14] Applying UpdateVisibilityRule to EcommerceCountryVisitorCountryProvider.php...
[2020-05-19 16:28:14] Applying ApiChangeWarningsRule to ShoppingCart.php...
[2020-05-19 16:28:15] Applying UpdateVisibilityRule to ShoppingCart.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to ProductCollection.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to ProductCollection.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to OrderToArray.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to OrderToArray.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to EcommerceCodeFilter.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to EcommerceCodeFilter.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to OrderInvoiceEmail.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to OrderInvoiceEmail.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to OrderErrorEmail.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to OrderErrorEmail.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to EcommerceDummyMailer.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to EcommerceDummyMailer.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to OrderStatusEmail.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to OrderStatusEmail.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to OrderReceiptEmail.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to OrderReceiptEmail.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to OrderEmail.php...
[2020-05-19 16:28:16] Applying UpdateVisibilityRule to OrderEmail.php...
[2020-05-19 16:28:16] Applying ApiChangeWarningsRule to ProductImage.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to ProductImage.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommercePaymentSupportedMethodsProviderInterface.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommercePaymentSupportedMethodsProviderInterface.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to OrderStepInterface.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to OrderStepInterface.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommerceGEOipProvider.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommerceGEOipProvider.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EditableEcommerceObject.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EditableEcommerceObject.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to BuyableModel.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to BuyableModel.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommerceConfigDefinitions.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommerceConfigDefinitions.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommerceConfig.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommerceConfig.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommerceConfigAjaxDefinitions.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommerceConfigAjaxDefinitions.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommerceConfigAjax.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommerceConfigAjax.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to OrderModifierFormController.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to OrderModifierFormController.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to OrderStatusLogFormController.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to OrderStatusLogFormController.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommerceResponse.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommerceResponse.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to EcommercePaymentController.php...
[2020-05-19 16:28:17] Applying UpdateVisibilityRule to EcommercePaymentController.php...
[2020-05-19 16:28:17] Applying ApiChangeWarningsRule to BuyableSelectFieldDataList.php...
[2020-05-19 16:28:18] Applying UpdateVisibilityRule to BuyableSelectFieldDataList.php...
[2020-05-19 16:28:18] Applying ApiChangeWarningsRule to EcommerceSiteTreeExtensionController.php...
[2020-05-19 16:28:18] Applying UpdateVisibilityRule to EcommerceSiteTreeExtensionController.php...
[2020-05-19 16:28:18] Applying ApiChangeWarningsRule to EcommerceTemplateTest.php...
[2020-05-19 16:28:18] Applying UpdateVisibilityRule to EcommerceTemplateTest.php...
[2020-05-19 16:28:18] Applying ApiChangeWarningsRule to OrderStepController.php...
[2020-05-19 16:28:19] Applying UpdateVisibilityRule to OrderStepController.php...
[2020-05-19 16:28:19] Applying ApiChangeWarningsRule to ShoppingCartController.php...
[2020-05-19 16:28:20] Applying UpdateVisibilityRule to ShoppingCartController.php...
[2020-05-19 16:28:20] Applying ApiChangeWarningsRule to CartResponse.php...
[2020-05-19 16:28:20] Applying UpdateVisibilityRule to CartResponse.php...
[2020-05-19 16:28:20] Applying ApiChangeWarningsRule to OrderEmailRecordReview.php...
[2020-05-19 16:28:20] Applying UpdateVisibilityRule to OrderEmailRecordReview.php...
[2020-05-19 16:28:20] Applying ApiChangeWarningsRule to ProductOrderItem.php...
[2020-05-19 16:28:20] Applying UpdateVisibilityRule to ProductOrderItem.php...
[2020-05-19 16:28:20] Applying ApiChangeWarningsRule to EcommerceDBConfig.php...
[2020-05-19 16:28:21] Applying UpdateVisibilityRule to EcommerceDBConfig.php...
[2020-05-19 16:28:22] Applying ApiChangeWarningsRule to EcommerceRegionVisitorRegionProvider.php...
[2020-05-19 16:28:22] Applying UpdateVisibilityRule to EcommerceRegionVisitorRegionProvider.php...
[2020-05-19 16:28:22] Applying ApiChangeWarningsRule to EcommerceRegion.php...
[2020-05-19 16:28:22] Applying UpdateVisibilityRule to EcommerceRegion.php...
[2020-05-19 16:28:22] Applying ApiChangeWarningsRule to ShippingAddress.php...
[2020-05-19 16:28:22] Applying UpdateVisibilityRule to ShippingAddress.php...
[2020-05-19 16:28:22] Applying ApiChangeWarningsRule to EcommerceCountry.php...
[2020-05-19 16:28:23] Applying UpdateVisibilityRule to EcommerceCountry.php...
[2020-05-19 16:28:23] Applying ApiChangeWarningsRule to OrderAddress.php...
[2020-05-19 16:28:24] Applying UpdateVisibilityRule to OrderAddress.php...
[2020-05-19 16:28:24] Applying ApiChangeWarningsRule to BillingAddress.php...
[2020-05-19 16:28:24] Applying UpdateVisibilityRule to BillingAddress.php...
[2020-05-19 16:28:24] Applying ApiChangeWarningsRule to OrderAttribute.php...
[2020-05-19 16:28:24] Applying UpdateVisibilityRule to OrderAttribute.php...
[2020-05-19 16:28:25] Applying ApiChangeWarningsRule to CheckoutPageStepDescription.php...
[2020-05-19 16:28:25] Applying UpdateVisibilityRule to CheckoutPageStepDescription.php...
[2020-05-19 16:28:25] Applying ApiChangeWarningsRule to OrderStepCreated.php...
[2020-05-19 16:28:25] Applying UpdateVisibilityRule to OrderStepCreated.php...
[2020-05-19 16:28:25] Applying ApiChangeWarningsRule to OrderStepSentReceipt.php...
[2020-05-19 16:28:25] Applying UpdateVisibilityRule to OrderStepSentReceipt.php...
[2020-05-19 16:28:25] Applying ApiChangeWarningsRule to OrderStepSendAdminNotification.php...
[2020-05-19 16:28:25] Applying UpdateVisibilityRule to OrderStepSendAdminNotification.php...
[2020-05-19 16:28:25] Applying ApiChangeWarningsRule to OrderStepSent.php...
[2020-05-19 16:28:25] Applying UpdateVisibilityRule to OrderStepSent.php...
[2020-05-19 16:28:25] Applying ApiChangeWarningsRule to OrderStepSubmitted.php...
[2020-05-19 16:28:26] Applying UpdateVisibilityRule to OrderStepSubmitted.php...
[2020-05-19 16:28:26] Applying ApiChangeWarningsRule to OrderStepArchived.php...
[2020-05-19 16:28:26] Applying UpdateVisibilityRule to OrderStepArchived.php...
[2020-05-19 16:28:26] Applying ApiChangeWarningsRule to OrderStepConfirmed.php...
[2020-05-19 16:28:26] Applying UpdateVisibilityRule to OrderStepConfirmed.php...
[2020-05-19 16:28:26] Applying ApiChangeWarningsRule to OrderStepPaid.php...
[2020-05-19 16:28:26] Applying UpdateVisibilityRule to OrderStepPaid.php...
[2020-05-19 16:28:26] Applying ApiChangeWarningsRule to OrderStepSentInvoice.php...
[2020-05-19 16:28:26] Applying UpdateVisibilityRule to OrderStepSentInvoice.php...
[2020-05-19 16:28:26] Applying ApiChangeWarningsRule to OrderStep.php...
[2020-05-19 16:28:27] Applying UpdateVisibilityRule to OrderStep.php...
[2020-05-19 16:28:27] Applying ApiChangeWarningsRule to OrderEmailRecord.php...
[2020-05-19 16:28:28] Applying UpdateVisibilityRule to OrderEmailRecord.php...
[2020-05-19 16:28:28] Applying ApiChangeWarningsRule to OrderProcessQueue.php...
[2020-05-19 16:28:28] Applying UpdateVisibilityRule to OrderProcessQueue.php...
[2020-05-19 16:28:28] Applying ApiChangeWarningsRule to OrderStatusLogCancel.php...
[2020-05-19 16:28:28] Applying UpdateVisibilityRule to OrderStatusLogCancel.php...
[2020-05-19 16:28:28] Applying ApiChangeWarningsRule to OrderStatusLogDispatchElectronicOrder.php...
[2020-05-19 16:28:28] Applying UpdateVisibilityRule to OrderStatusLogDispatchElectronicOrder.php...
[2020-05-19 16:28:28] Applying ApiChangeWarningsRule to OrderStatusLogDispatch.php...
[2020-05-19 16:28:28] Applying UpdateVisibilityRule to OrderStatusLogDispatch.php...
[2020-05-19 16:28:28] Applying ApiChangeWarningsRule to OrderStatusLogSubmitted.php...
[2020-05-19 16:28:28] Applying UpdateVisibilityRule to OrderStatusLogSubmitted.php...
[2020-05-19 16:28:28] Applying ApiChangeWarningsRule to OrderStatusLogArchived.php...
[2020-05-19 16:28:29] Applying UpdateVisibilityRule to OrderStatusLogArchived.php...
[2020-05-19 16:28:29] Applying ApiChangeWarningsRule to OrderStatusLogPaymentCheck.php...
[2020-05-19 16:28:29] Applying UpdateVisibilityRule to OrderStatusLogPaymentCheck.php...
[2020-05-19 16:28:29] Applying ApiChangeWarningsRule to OrderStatusLogDispatchPhysicalOrder.php...
[2020-05-19 16:28:29] Applying UpdateVisibilityRule to OrderStatusLogDispatchPhysicalOrder.php...
[2020-05-19 16:28:29] Applying ApiChangeWarningsRule to OrderStatusLog.php...
[2020-05-19 16:28:29] Applying UpdateVisibilityRule to OrderStatusLog.php...
[2020-05-19 16:28:29] Applying ApiChangeWarningsRule to OrderFeedback.php...
[2020-05-19 16:28:29] Applying UpdateVisibilityRule to OrderFeedback.php...
[2020-05-19 16:28:29] Applying ApiChangeWarningsRule to EcommerceCurrency.php...
[2020-05-19 16:28:30] Applying UpdateVisibilityRule to EcommerceCurrency.php...
[2020-05-19 16:28:30] Applying ApiChangeWarningsRule to EcommercePaymentTest.php...
[2020-05-19 16:28:30] Applying UpdateVisibilityRule to EcommercePaymentTest.php...
[2020-05-19 16:28:30] Applying ApiChangeWarningsRule to EcommercePaymentTestPending.php...
[2020-05-19 16:28:30] Applying UpdateVisibilityRule to EcommercePaymentTestPending.php...
[2020-05-19 16:28:30] Applying ApiChangeWarningsRule to EcommercePaymentTestSuccess.php...
[2020-05-19 16:28:30] Applying UpdateVisibilityRule to EcommercePaymentTestSuccess.php...
[2020-05-19 16:28:30] Applying ApiChangeWarningsRule to EcommercePaymentTestFailure.php...
[2020-05-19 16:28:30] Applying UpdateVisibilityRule to EcommercePaymentTestFailure.php...
[2020-05-19 16:28:30] Applying ApiChangeWarningsRule to EcommercePayment.php...
[2020-05-19 16:28:31] Applying UpdateVisibilityRule to EcommercePayment.php...
[2020-05-19 16:28:31] Applying ApiChangeWarningsRule to OrderModifierDescriptor.php...
[2020-05-19 16:28:31] Applying UpdateVisibilityRule to OrderModifierDescriptor.php...
[2020-05-19 16:28:31] Applying ApiChangeWarningsRule to OrderItem.php...
[2020-05-19 16:28:32] Applying UpdateVisibilityRule to OrderItem.php...
[2020-05-19 16:28:33] Applying ApiChangeWarningsRule to OrderModifier.php...
[2020-05-19 16:28:33] Applying UpdateVisibilityRule to OrderModifier.php...
[2020-05-19 16:28:33] Applying ApiChangeWarningsRule to SearchReplacement.php...
[2020-05-19 16:28:33] Applying UpdateVisibilityRule to SearchReplacement.php...
[2020-05-19 16:28:33] Applying ApiChangeWarningsRule to SearchHistory.php...
[2020-05-19 16:28:33] Applying UpdateVisibilityRule to SearchHistory.php...
[2020-05-19 16:28:33] Applying ApiChangeWarningsRule to Order.php...
[2020-05-19 16:28:37] Applying UpdateVisibilityRule to Order.php...
[2020-05-19 16:28:39] Applying ApiChangeWarningsRule to BuyableFieldType.php...
[2020-05-19 16:28:39] Applying UpdateVisibilityRule to BuyableFieldType.php...
[2020-05-19 16:28:39] Applying ApiChangeWarningsRule to OrderAttributeGroup.php...
[2020-05-19 16:28:39] Applying UpdateVisibilityRule to OrderAttributeGroup.php...
[2020-05-19 16:28:39] Applying ApiChangeWarningsRule to EcommerceRole.php...
[2020-05-19 16:28:40] Applying UpdateVisibilityRule to EcommerceRole.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to EcommerceSiteTreeExtension.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to EcommerceSiteTreeExtension.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldAddNewButtonOriginalPage.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldAddNewButtonOriginalPage.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldBasicPageRelationConfig.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldBasicPageRelationConfig.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldEditOriginalPageConfigWithDelete.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldEditOriginalPageConfigWithDelete.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldEditOriginalPageConfigWithAddExisting.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldEditOriginalPageConfigWithAddExisting.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldConfigForOrderItems.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldConfigForOrderItems.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldBasicPageRelationConfigNoAddExisting.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldBasicPageRelationConfigNoAddExisting.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldEditOriginalPageConfig.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldEditOriginalPageConfig.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldPrintInvoiceButton.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldPrintInvoiceButton.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldExportSalesButton.php...
[2020-05-19 16:28:41] Applying UpdateVisibilityRule to GridFieldExportSalesButton.php...
[2020-05-19 16:28:41] Applying ApiChangeWarningsRule to GridFieldEditButtonOriginalPage.php...
[2020-05-19 16:28:42] Applying UpdateVisibilityRule to GridFieldEditButtonOriginalPage.php...
[2020-05-19 16:28:42] Applying ApiChangeWarningsRule to GridFieldPrintAllPackingSlipsButton.php...
[2020-05-19 16:28:42] Applying UpdateVisibilityRule to GridFieldPrintAllPackingSlipsButton.php...
[2020-05-19 16:28:42] Applying ApiChangeWarningsRule to GridFieldPrintAllInvoicesButton.php...
[2020-05-19 16:28:42] Applying UpdateVisibilityRule to GridFieldPrintAllInvoicesButton.php...
[2020-05-19 16:28:42] Applying ApiChangeWarningsRule to GridFieldPrintPackingSlipButton.php...
[2020-05-19 16:28:42] Applying UpdateVisibilityRule to GridFieldPrintPackingSlipButton.php...
[2020-05-19 16:28:42] Applying ApiChangeWarningsRule to OrderFormAddress.php...
[2020-05-19 16:28:43] Applying UpdateVisibilityRule to OrderFormAddress.php...
[2020-05-19 16:28:43] Applying ApiChangeWarningsRule to OrderFormPayment.php...
[2020-05-19 16:28:43] Applying UpdateVisibilityRule to OrderFormPayment.php...
[2020-05-19 16:28:43] Applying ApiChangeWarningsRule to OrderFormCancel.php...
[2020-05-19 16:28:43] Applying UpdateVisibilityRule to OrderFormCancel.php...
[2020-05-19 16:28:43] Applying ApiChangeWarningsRule to ProductSearchFormShort.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to ProductSearchFormShort.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to OptionalTreeDropdownField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to OptionalTreeDropdownField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to EcomQuantityField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to EcomQuantityField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to EcommerceSearchHistoryFormField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to EcommerceSearchHistoryFormField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to OrderStepField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to OrderStepField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to BuyableSelectField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to BuyableSelectField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to SelectOrderAddressField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to SelectOrderAddressField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to EcommerceClassNameOrTypeDropdownField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to EcommerceClassNameOrTypeDropdownField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to EcommerceCreditCardField.php...
[2020-05-19 16:28:44] Applying UpdateVisibilityRule to EcommerceCreditCardField.php...
[2020-05-19 16:28:44] Applying ApiChangeWarningsRule to EcommerceCMSButtonField.php...
[2020-05-19 16:28:45] Applying UpdateVisibilityRule to EcommerceCMSButtonField.php...
[2020-05-19 16:28:45] Applying ApiChangeWarningsRule to ExpiryDateField.php...
[2020-05-19 16:28:45] Applying UpdateVisibilityRule to ExpiryDateField.php...
[2020-05-19 16:28:45] Applying ApiChangeWarningsRule to ProductProductImageUploadField.php...
[2020-05-19 16:28:45] Applying UpdateVisibilityRule to ProductProductImageUploadField.php...
[2020-05-19 16:28:45] Applying ApiChangeWarningsRule to OrderForm.php...
[2020-05-19 16:28:45] Applying UpdateVisibilityRule to OrderForm.php...
[2020-05-19 16:28:45] Applying ApiChangeWarningsRule to OrderFormAddressValidator.php...
[2020-05-19 16:28:45] Applying UpdateVisibilityRule to OrderFormAddressValidator.php...
[2020-05-19 16:28:45] Applying ApiChangeWarningsRule to ShopAccountFormValidator.php...
[2020-05-19 16:28:45] Applying UpdateVisibilityRule to ShopAccountFormValidator.php...
[2020-05-19 16:28:45] Applying ApiChangeWarningsRule to OrderFormCancelValidator.php...
[2020-05-19 16:28:45] Applying UpdateVisibilityRule to OrderFormCancelValidator.php...
[2020-05-19 16:28:45] Applying ApiChangeWarningsRule to OrderFormValidator.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to OrderFormValidator.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to OrderStatusLogFormValidator.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to OrderStatusLogFormValidator.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to EcommercePaymentFormSetupAndValidation.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to EcommercePaymentFormSetupAndValidation.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to OrderFormPaymentValidator.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to OrderFormPaymentValidator.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to ProductSearchFormValidator.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to ProductSearchFormValidator.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to ShopAccountFormPasswordValidator.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to ShopAccountFormPasswordValidator.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to OrderModifierFormValidator.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to OrderModifierFormValidator.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to OrderFormFeedbackValidator.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to OrderFormFeedbackValidator.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to OrderFormFeedback.php...
[2020-05-19 16:28:46] Applying UpdateVisibilityRule to OrderFormFeedback.php...
[2020-05-19 16:28:46] Applying ApiChangeWarningsRule to ShopAccountForm.php...
[2020-05-19 16:28:47] Applying UpdateVisibilityRule to ShopAccountForm.php...
[2020-05-19 16:28:47] Applying ApiChangeWarningsRule to OrderStatusLogForm.php...
[2020-05-19 16:28:47] Applying UpdateVisibilityRule to OrderStatusLogForm.php...
[2020-05-19 16:28:47] Applying ApiChangeWarningsRule to OrderModifierForm.php...
[2020-05-19 16:28:47] Applying UpdateVisibilityRule to OrderModifierForm.php...
[2020-05-19 16:28:47] Applying ApiChangeWarningsRule to ProductSearchForm.php...
[2020-05-19 16:28:48] Applying UpdateVisibilityRule to ProductSearchForm.php...
[2020-05-19 16:28:48] Applying ApiChangeWarningsRule to EcommerceDevelopmentAdminDecorator.php...
[2020-05-19 16:28:48] Applying UpdateVisibilityRule to EcommerceDevelopmentAdminDecorator.php...
[2020-05-19 16:28:48] Applying ApiChangeWarningsRule to EcommerceDatabaseAdmin.php...
[2020-05-19 16:28:48] Applying UpdateVisibilityRule to EcommerceDatabaseAdmin.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to EcommerceDatabaseAdminDebugView.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to EcommerceDatabaseAdminDebugView.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to SalesAdmin.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to SalesAdmin.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to ProductsAndGroupsModelAdmin.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to ProductsAndGroupsModelAdmin.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to CMSPageAddControllerProducts.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to CMSPageAddControllerProducts.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to ModelAdminEcommerceBaseClass.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to ModelAdminEcommerceBaseClass.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to SalesAdminExtras.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to SalesAdminExtras.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to ProductConfigModelAdmin.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to ProductConfigModelAdmin.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to ProductBulkLoader.php...
[2020-05-19 16:28:49] Applying UpdateVisibilityRule to ProductBulkLoader.php...
[2020-05-19 16:28:49] Applying ApiChangeWarningsRule to StoreAdmin.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to StoreAdmin.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceMoney.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceMoney.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommercePaymentSupportedMethodsProvider.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommercePaymentSupportedMethodsProvider.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommercePaymentResult.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommercePaymentResult.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommercePaymentProcessing.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommercePaymentProcessing.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommercePaymentSuccess.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommercePaymentSuccess.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommercePaymentFailure.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommercePaymentFailure.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to ExchangeRateProviderDummy.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to ExchangeRateProviderDummy.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to ExchangeRateProvider.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to ExchangeRateProvider.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskCountryAndRegionDisallowAllCountries.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceTaskCountryAndRegionDisallowAllCountries.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskAPIandMore.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceTaskAPIandMore.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskTemplateTest.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceTaskTemplateTest.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskSetOrderIDStartingNumber.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceTaskSetOrderIDStartingNumber.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskAddDefaultProducts.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceTaskAddDefaultProducts.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskCreateMemberGroups.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceTaskCreateMemberGroups.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskBuildingModel.php...
[2020-05-19 16:28:50] Applying UpdateVisibilityRule to EcommerceTaskBuildingModel.php...
[2020-05-19 16:28:50] Applying ApiChangeWarningsRule to EcommerceTaskLinkProductsWithImages.php...
[2020-05-19 16:28:51] Applying UpdateVisibilityRule to EcommerceTaskLinkProductsWithImages.php...
[2020-05-19 16:28:51] Applying ApiChangeWarningsRule to EcommerceTaskOrdersWithoutOrderStep.php...
[2020-05-19 16:28:51] Applying UpdateVisibilityRule to EcommerceTaskOrdersWithoutOrderStep.php...
[2020-05-19 16:28:51] Applying ApiChangeWarningsRule to EcommerceTaskCartManipulationDebug.php...
[2020-05-19 16:28:51] Applying UpdateVisibilityRule to EcommerceTaskCartManipulationDebug.php...
[2020-05-19 16:28:51] Applying ApiChangeWarningsRule to EcommerceTaskProductVariationsFixes.php...
[2020-05-19 16:28:51] Applying UpdateVisibilityRule to EcommerceTaskProductVariationsFixes.php...
[2020-05-19 16:28:51] Applying ApiChangeWarningsRule to EcommerceTaskArchiveAllOrdersWithItems.php...
[2020-05-19 16:28:51] Applying UpdateVisibilityRule to EcommerceTaskArchiveAllOrdersWithItems.php...
[2020-05-19 16:28:51] Applying ApiChangeWarningsRule to EcommerceTaskAddCustomersToCustomerGroups.php...
[2020-05-19 16:28:51] Applying UpdateVisibilityRule to EcommerceTaskAddCustomersToCustomerGroups.php...
[2020-05-19 16:28:51] Applying ApiChangeWarningsRule to EcommerceTaskBuildingExtending.php...
[2020-05-19 16:28:51] Applying UpdateVisibilityRule to EcommerceTaskBuildingExtending.php...
[2020-05-19 16:28:51] Applying ApiChangeWarningsRule to EcommerceTaskProductImageReset.php...
[2020-05-19 16:28:52] Applying UpdateVisibilityRule to EcommerceTaskProductImageReset.php...
[2020-05-19 16:28:52] Applying ApiChangeWarningsRule to EcommerceTaskArchiveAllSubmittedOrders.php...
[2020-05-19 16:28:52] Applying UpdateVisibilityRule to EcommerceTaskArchiveAllSubmittedOrders.php...
[2020-05-19 16:28:52] Applying ApiChangeWarningsRule to EcommerceTaskCartCleanup.php...
[2020-05-19 16:28:52] Applying UpdateVisibilityRule to EcommerceTaskCartCleanup.php...
[2020-05-19 16:28:53] Applying ApiChangeWarningsRule to EcommerceTaskTryToFinaliseOrders.php...
[2020-05-19 16:28:53] Applying UpdateVisibilityRule to EcommerceTaskTryToFinaliseOrders.php...
[2020-05-19 16:28:53] Applying ApiChangeWarningsRule to EcommerceTaskCheckConfiguration.php...
[2020-05-19 16:28:54] Applying UpdateVisibilityRule to EcommerceTaskCheckConfiguration.php...
[2020-05-19 16:28:55] Applying ApiChangeWarningsRule to EcommerceTaskDeleteAllOrders.php...
[2020-05-19 16:28:55] Applying UpdateVisibilityRule to EcommerceTaskDeleteAllOrders.php...
[2020-05-19 16:28:55] Applying ApiChangeWarningsRule to EcommerceTaskProcessOrderQueue.php...
[2020-05-19 16:28:55] Applying UpdateVisibilityRule to EcommerceTaskProcessOrderQueue.php...
[2020-05-19 16:28:55] Applying ApiChangeWarningsRule to EcommerceTaskLinkOrderAddressesAtBothEnds.php...
[2020-05-19 16:28:55] Applying UpdateVisibilityRule to EcommerceTaskLinkOrderAddressesAtBothEnds.php...
[2020-05-19 16:28:55] Applying ApiChangeWarningsRule to EcommerceTaskReviewSearches.php...
[2020-05-19 16:28:56] Applying UpdateVisibilityRule to EcommerceTaskReviewSearches.php...
[2020-05-19 16:28:56] Applying ApiChangeWarningsRule to EcommerceTaskLinkProductWithImages.php...
[2020-05-19 16:28:56] Applying UpdateVisibilityRule to EcommerceTaskLinkProductWithImages.php...
[2020-05-19 16:28:56] Applying ApiChangeWarningsRule to EcommerceTaskCleanupProducts.php...
[2020-05-19 16:28:56] Applying UpdateVisibilityRule to EcommerceTaskCleanupProducts.php...
[2020-05-19 16:28:56] Applying ApiChangeWarningsRule to EcommerceTaskReviewReports.php...
[2020-05-19 16:28:56] Applying UpdateVisibilityRule to EcommerceTaskReviewReports.php...
[2020-05-19 16:28:56] Applying ApiChangeWarningsRule to EcommerceTaskSetDefaultProductGroupValues.php...
[2020-05-19 16:28:56] Applying UpdateVisibilityRule to EcommerceTaskSetDefaultProductGroupValues.php...
[2020-05-19 16:28:56] Applying ApiChangeWarningsRule to EcommerceTaskCartManipulationCurrent.php...
[2020-05-19 16:28:56] Applying UpdateVisibilityRule to EcommerceTaskCartManipulationCurrent.php...
[2020-05-19 16:28:56] Applying ApiChangeWarningsRule to EcommerceTaskFixBrokenOrderSubmissionData.php...
[2020-05-19 16:28:56] Applying UpdateVisibilityRule to EcommerceTaskFixBrokenOrderSubmissionData.php...
[2020-05-19 16:28:56] Applying ApiChangeWarningsRule to EcommerceTaskDefaultRecords.php...
[2020-05-19 16:28:57] Applying UpdateVisibilityRule to EcommerceTaskDefaultRecords.php...
[2020-05-19 16:28:57] Applying ApiChangeWarningsRule to EcommerceTaskCleanupProductFullSiteTreeSorting.php...
[2020-05-19 16:28:57] Applying UpdateVisibilityRule to EcommerceTaskCleanupProductFullSiteTreeSorting.php...
[2020-05-19 16:28:57] Applying ApiChangeWarningsRule to EcommerceTaskCountryAndRegion.php...
[2020-05-19 16:28:57] Applying UpdateVisibilityRule to EcommerceTaskCountryAndRegion.php...
[2020-05-19 16:28:57] Applying ApiChangeWarningsRule to EcommerceTaskDeleteProducts.php...
[2020-05-19 16:28:57] Applying UpdateVisibilityRule to EcommerceTaskDeleteProducts.php...
[2020-05-19 16:28:57] Applying ApiChangeWarningsRule to EcommerceTaskCountryAndRegionAllowAllCountries.php...
[2020-05-19 16:28:57] Applying UpdateVisibilityRule to EcommerceTaskCountryAndRegionAllowAllCountries.php...
[2020-05-19 16:28:57] Applying ApiChangeWarningsRule to EcommerceTaskDebugCart.php...
[2020-05-19 16:28:57] Applying UpdateVisibilityRule to EcommerceTaskDebugCart.php...
[2020-05-19 16:28:57] Applying ApiChangeWarningsRule to EcommerceTaskOrderItemsPerCustomer.php...
[2020-05-19 16:28:57] Applying UpdateVisibilityRule to EcommerceTaskOrderItemsPerCustomer.php...
[2020-05-19 16:28:57] Applying ApiChangeWarningsRule to OrderFiltersMustHaveAtLeastOnePayment.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to OrderFiltersMustHaveAtLeastOnePayment.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to OrderFiltersAroundDateFilter.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to OrderFiltersAroundDateFilter.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to OrderFiltersMultiOptionsetStatusIDFilter.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to OrderFiltersMultiOptionsetStatusIDFilter.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to OrderFiltersMemberAndAddress.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to OrderFiltersMemberAndAddress.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to OrderFiltersHasBeenCancelled.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to OrderFiltersHasBeenCancelled.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to OrderEmailRecordFiltersMultiOptionsetStatusIDFilter.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to OrderEmailRecordFiltersMultiOptionsetStatusIDFilter.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to EcommercePaymentFiltersAroundDateFilter.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to EcommercePaymentFiltersAroundDateFilter.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to CheckoutPageController.php...
[2020-05-19 16:28:58] Applying UpdateVisibilityRule to CheckoutPageController.php...
[2020-05-19 16:28:58] Applying ApiChangeWarningsRule to ProductGroupController.php...
[2020-05-19 16:29:00] Applying UpdateVisibilityRule to ProductGroupController.php...
[2020-05-19 16:29:00] Applying ApiChangeWarningsRule to AccountPageController.php...
[2020-05-19 16:29:00] Applying UpdateVisibilityRule to AccountPageController.php...
[2020-05-19 16:29:00] Applying ApiChangeWarningsRule to ProductController.php...
[2020-05-19 16:29:01] Applying UpdateVisibilityRule to ProductController.php...
[2020-05-19 16:29:01] Applying ApiChangeWarningsRule to OrderConfirmationPage.php...
[2020-05-19 16:29:01] Applying UpdateVisibilityRule to OrderConfirmationPage.php...
[2020-05-19 16:29:01] Applying ApiChangeWarningsRule to ProductGroupSearchPage.php...
[2020-05-19 16:29:01] Applying UpdateVisibilityRule to ProductGroupSearchPage.php...
[2020-05-19 16:29:01] Applying ApiChangeWarningsRule to CartPageController.php...
[2020-05-19 16:29:02] Applying UpdateVisibilityRule to CartPageController.php...
[2020-05-19 16:29:03] Applying ApiChangeWarningsRule to CheckoutPage.php...
[2020-05-19 16:29:03] Applying UpdateVisibilityRule to CheckoutPage.php...
[2020-05-19 16:29:03] Applying ApiChangeWarningsRule to AccountPage.php...
[2020-05-19 16:29:03] Applying UpdateVisibilityRule to AccountPage.php...
[2020-05-19 16:29:03] Applying ApiChangeWarningsRule to ProductGroupSearchPageController.php...
[2020-05-19 16:29:03] Applying UpdateVisibilityRule to ProductGroupSearchPageController.php...
[2020-05-19 16:29:03] Applying ApiChangeWarningsRule to Product.php...
[2020-05-19 16:29:05] Applying UpdateVisibilityRule to Product.php...
[2020-05-19 16:29:05] Applying ApiChangeWarningsRule to ProductGroup.php...
[2020-05-19 16:29:06] Applying UpdateVisibilityRule to ProductGroup.php...
[2020-05-19 16:29:07] Applying ApiChangeWarningsRule to CartPage.php...
[2020-05-19 16:29:08] Applying UpdateVisibilityRule to CartPage.php...
[2020-05-19 16:29:08] Applying ApiChangeWarningsRule to OrderConfirmationPageController.php...
[2020-05-19 16:29:08] Applying UpdateVisibilityRule to OrderConfirmationPageController.php...
[2020-05-19 16:29:08] Applying ApiChangeWarningsRule to EcommerceSideReportEcommercePages.php...
[2020-05-19 16:29:08] Applying UpdateVisibilityRule to EcommerceSideReportEcommercePages.php...
[2020-05-19 16:29:08] Applying ApiChangeWarningsRule to EcommerceSideReportAllProducts.php...
[2020-05-19 16:29:08] Applying UpdateVisibilityRule to EcommerceSideReportAllProducts.php...
[2020-05-19 16:29:08] Applying ApiChangeWarningsRule to EcommerceSideReportNoPriceProducts.php...
[2020-05-19 16:29:08] Applying UpdateVisibilityRule to EcommerceSideReportNoPriceProducts.php...
[2020-05-19 16:29:08] Applying ApiChangeWarningsRule to EcommerceSideReportFeaturedProducts.php...
[2020-05-19 16:29:09] Applying UpdateVisibilityRule to EcommerceSideReportFeaturedProducts.php...
[2020-05-19 16:29:09] Applying ApiChangeWarningsRule to EcommerceSideReportNoImageProducts.php...
[2020-05-19 16:29:09] Applying UpdateVisibilityRule to EcommerceSideReportNoImageProducts.php...
[2020-05-19 16:29:09] Applying ApiChangeWarningsRule to EcommerceSideReportNotForSale.php...
[2020-05-19 16:29:09] Applying UpdateVisibilityRule to EcommerceSideReportNotForSale.php...
[2020-05-19 16:29:09] Applying ApiChangeWarningsRule to EcommerceSideReportNoInternalIDProducts.php...
[2020-05-19 16:29:09] Applying UpdateVisibilityRule to EcommerceSideReportNoInternalIDProducts.php...
modified:	Email/OrderEmail.php
@@ -142,7 +142,7 @@
             if (EcommerceConfig::get(OrderEmail::class, 'copy_to_admin_for_all_emails') && ($this->to !== self::get_from_email())) {
                 if ($memberEmail = self::get_from_email()) {
                     $array = [$memberEmail];
-                    if ($bcc = $this->Bcc()) {
+                    if ($bcc = $this->getBcc()) {
                         $array[] = $bcc;
                     }
                     $this->setBcc(implode(', ', $array));
@@ -151,7 +151,7 @@
             //last chance to adjust
             $this->extend('adjustOrderEmailSending', $this, $order);
             if ($returnBodyOnly) {
-                return $this->Body();
+                return $this->getBody();
             }

             if (EcommerceConfig::get(OrderEmail::class, 'send_all_emails_plain')) {
@@ -218,11 +218,11 @@
         $orderEmailRecord = OrderEmailRecord::create();
         $orderEmailRecord->From = $this->emailToVarchar($this->from);
         $orderEmailRecord->To = $this->emailToVarchar($this->to);
-        if ($this->Cc()) {
-            $orderEmailRecord->To .= ', CC: ' . $this->emailToVarchar($this->Cc());
-        }
-        if ($this->Bcc()) {
-            $orderEmailRecord->To .= ', BCC: ' . $this->emailToVarchar($this->Bcc());
+        if ($this->getCc()) {
+            $orderEmailRecord->To .= ', CC: ' . $this->emailToVarchar($this->getCc());
+        }
+        if ($this->getBcc()) {
+            $orderEmailRecord->To .= ', BCC: ' . $this->emailToVarchar($this->getBcc());
         }
         //always set result to try if
         $orderEmailRecord->Subject = $this->subject;

Warnings for Email/OrderEmail.php:
 - Email/OrderEmail.php:145 SilverStripe\Control\Email\Email->Bcc(): Renamed to getBcc()
 - Email/OrderEmail.php:154 SilverStripe\Control\Email\Email->Body(): Renamed to getBody()
 - Email/OrderEmail.php:221 SilverStripe\Control\Email\Email->Cc(): Renamed to getCc()
 - Email/OrderEmail.php:222 SilverStripe\Control\Email\Email->Cc(): Renamed to getCc()
 - Email/OrderEmail.php:224 SilverStripe\Control\Email\Email->Bcc(): Renamed to getBcc()
 - Email/OrderEmail.php:225 SilverStripe\Control\Email\Email->Bcc(): Renamed to getBcc()
unchanged:	Control/OrderModifierFormController.php
Warnings for Control/OrderModifierFormController.php:
 - Control/OrderModifierFormController.php:77 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Control/OrderStatusLogFormController.php
Warnings for Control/OrderStatusLogFormController.php:
 - Control/OrderStatusLogFormController.php:71 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Control/EcommercePaymentController.php
Warnings for Control/EcommercePaymentController.php:
 - Control/EcommercePaymentController.php:143 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
modified:	Control/BuyableSelectFieldDataList.php
@@ -101,7 +101,7 @@
   * ### @@@@ STOP REPLACEMENT @@@@ ###
   */
             if (is_a($singleton, SilverStripe\Core\Injector\Injector::inst()->getCustomClass(SiteTree::class))) {
-                if (Versioned::current_stage() === 'Live') {
+                if (Versioned::get_stage() === 'Live') {
                     $buyables[$key]['TableName'] .= '_Live';
                 }
             }

Warnings for Control/BuyableSelectFieldDataList.php:
 - Control/BuyableSelectFieldDataList.php:104 SilverStripe\Versioned\Versioned::current_stage(): Moved to SilverStripe\Versioned\Versioned::get_stage()
 - Control/BuyableSelectFieldDataList.php:51 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Model/ProductOrderItem.php
Warnings for Model/ProductOrderItem.php:
 - Model/ProductOrderItem.php:67 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Model/Address/ShippingAddress.php
Warnings for Model/Address/ShippingAddress.php:
 - Model/Address/ShippingAddress.php:274 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Address/BillingAddress.php
Warnings for Model/Address/BillingAddress.php:
 - Model/Address/BillingAddress.php:276 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Process/OrderSteps/OrderStepCreated.php
Warnings for Model/Process/OrderSteps/OrderStepCreated.php:
 - Model/Process/OrderSteps/OrderStepCreated.php:138 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Process/OrderSteps/OrderStepSentReceipt.php
Warnings for Model/Process/OrderSteps/OrderStepSentReceipt.php:
 - Model/Process/OrderSteps/OrderStepSentReceipt.php:75 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Process/OrderSteps/OrderStepSent.php
Warnings for Model/Process/OrderSteps/OrderStepSent.php:
 - Model/Process/OrderSteps/OrderStepSent.php:90 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Process/OrderSteps/OrderStepSent.php:94 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Process/OrderSteps/OrderStepSubmitted.php
Warnings for Model/Process/OrderSteps/OrderStepSubmitted.php:
 - Model/Process/OrderSteps/OrderStepSubmitted.php:93 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Process/OrderSteps/OrderStepSentInvoice.php
Warnings for Model/Process/OrderSteps/OrderStepSentInvoice.php:
 - Model/Process/OrderSteps/OrderStepSentInvoice.php:74 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Process/OrderStep.php
Warnings for Model/Process/OrderStep.php:
 - Model/Process/OrderStep.php:573 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Process/OrderStep.php:665 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Process/OrderStep.php:666 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Process/OrderStep.php:667 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Process/OrderStep.php:668 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Process/OrderStep.php:669 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Process/OrderStep.php:542 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Model/Money/EcommerceCurrency.php
Warnings for Model/Money/EcommerceCurrency.php:
 - Model/Money/EcommerceCurrency.php:540 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Model/Money/EcommercePayment.php
Warnings for Model/Money/EcommercePayment.php:
 - Model/Money/EcommercePayment.php:577 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Money/EcommercePayment.php:602 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
 - Model/Money/EcommercePayment.php:625 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
 - Model/Money/EcommercePayment.php:667 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Model/OrderItem.php
Warnings for Model/OrderItem.php:
 - Model/OrderItem.php:281 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/OrderItem.php:292 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/OrderItem.php:299 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/OrderItem.php:358 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/OrderItem.php:835 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Model/OrderModifier.php
Warnings for Model/OrderModifier.php:
 - Model/OrderModifier.php:930 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/OrderModifier.php:286 SilverStripe\Forms\Formfield->dontEscape: FormField::$dontEscape has been removed. Escaping is now managed on a class by class basis.
unchanged:	Model/Order.php
Warnings for Model/Order.php:
 - Model/Order.php:794 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:801 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:820 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:866 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:868 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:961 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:1058 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:1063 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Order.php:624 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Model/Extensions/EcommerceRole.php
Warnings for Model/Extensions/EcommerceRole.php:
 - Model/Extensions/EcommerceRole.php:518 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Extensions/EcommerceRole.php:584 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Extensions/EcommerceRole.php:593 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Model/Extensions/EcommerceRole.php:506 SilverStripe\Forms\Formfield->dontEscape: FormField::$dontEscape has been removed. Escaping is now managed on a class by class basis.
 - Model/Extensions/EcommerceRole.php:509 SilverStripe\Forms\Formfield->dontEscape: FormField::$dontEscape has been removed. Escaping is now managed on a class by class basis.
 - Model/Extensions/EcommerceRole.php:512 SilverStripe\Forms\Formfield->dontEscape: FormField::$dontEscape has been removed. Escaping is now managed on a class by class basis.
modified:	Forms/Gridfield/GridFieldAddNewButtonOriginalPage.php
@@ -86,7 +86,7 @@
                 return $result;
             }
             $stage = '';
-            if (Versioned::current_stage() === 'Live') {
+            if (Versioned::get_stage() === 'Live') {
                 $stage = '_Live';
             }
             if ($result = $rootParentClass::get()->filter('MyParentPage.ParentID', 0)->innerJoin(SiteTree::class . $stage, 'MyParentPage.ID = SiteTree' . $stage . '.ParentID', 'MyParentPage')->First()) {

Warnings for Forms/Gridfield/GridFieldAddNewButtonOriginalPage.php:
 - Forms/Gridfield/GridFieldAddNewButtonOriginalPage.php:89 SilverStripe\Versioned\Versioned::current_stage(): Moved to SilverStripe\Versioned\Versioned::get_stage()
unchanged:	Forms/OrderFormAddress.php
Warnings for Forms/OrderFormAddress.php:
 - Forms/OrderFormAddress.php:127 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Forms/OrderFormAddress.php:176 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Forms/OrderFormCancel.php
Warnings for Forms/OrderFormCancel.php:
 - Forms/OrderFormCancel.php:47 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
modified:	Forms/OrderForm.php
@@ -264,7 +264,7 @@
         }
         //there is an error with payment
         if (! Controller::curr()->redirectedTo()) {
-            $this->controller->redirect($order->Link());
+            $this->controller->redirect($order->getRequestHandler()->Link());
         }

         return false;

Warnings for Forms/OrderForm.php:
 - Forms/OrderForm.php:84 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Forms/OrderForm.php:100 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Forms/OrderForm.php:267 SilverStripe\Forms\Form->Link(): Moved to FormRequestHandler
unchanged:	Forms/Validation/OrderFormAddressValidator.php
Warnings for Forms/Validation/OrderFormAddressValidator.php:
 - Forms/Validation/OrderFormAddressValidator.php:59 SilverStripe\Forms\Form->messageForForm(): Use setMessage() or sessionMessage() instead
unchanged:	Forms/ShopAccountForm.php
Warnings for Forms/ShopAccountForm.php:
 - Forms/ShopAccountForm.php:77 SilverStripe\Forms\Formfield->dontEscape: FormField::$dontEscape has been removed. Escaping is now managed on a class by class basis.
unchanged:	Forms/OrderStatusLogForm.php
Warnings for Forms/OrderStatusLogForm.php:
 - Forms/OrderStatusLogForm.php:86 THIRDPARTY_DIR: Path constants have been deprecated. Use the Requirements and ModuleResourceLoader APIs (https://docs.silverstripe.org/en/4/changelogs/4.0.0#module-paths)
unchanged:	Forms/OrderModifierForm.php
Warnings for Forms/OrderModifierForm.php:
 - Forms/OrderModifierForm.php:86 THIRDPARTY_DIR: Path constants have been deprecated. Use the Requirements and ModuleResourceLoader APIs (https://docs.silverstripe.org/en/4/changelogs/4.0.0#module-paths)
modified:	Forms/ProductSearchForm.php
@@ -490,7 +490,7 @@
                     $list1 = $baseList->filter(['InternalItemID' => $keywordPhrase]);
                     $count = $list1->count();
                     if ($count === 1) {
-                        $immediateRedirectLink = $list1->First()->Link();
+                        $immediateRedirectLink = $list1->First()->getRequestHandler()->Link();
                         $this->controller->redirect($immediateRedirectLink);
                         $this->debugOutput('<p style="color: red">Found one answer for potential immediate redirect: ' . $immediateRedirectLink . '</p>');
                     }
@@ -698,7 +698,7 @@
         if ($immediateRedirectLink) {
             $link = $immediateRedirectLink;
         } else {
-            $link = $redirectToPage->Link($this->controllerSearchResultDisplayMethod);
+            $link = $redirectToPage->getRequestHandler()->Link($this->controllerSearchResultDisplayMethod);
         }
         if ($this->additionalGetParameters) {
             $link .= '?' . $this->additionalGetParameters;

Warnings for Forms/ProductSearchForm.php:
 - Forms/ProductSearchForm.php:493 SilverStripe\Forms\Form->Link(): Moved to FormRequestHandler
 - Forms/ProductSearchForm.php:701 SilverStripe\Forms\Form->Link(): Moved to FormRequestHandler
unchanged:	Cms/Dev/EcommerceDatabaseAdminDebugView.php
Warnings for Cms/Dev/EcommerceDatabaseAdminDebugView.php:
 - Cms/Dev/EcommerceDatabaseAdminDebugView.php:35 class: $this->class access has been removed (https://docs.silverstripe.org/en/4/changelogs/4.0.0#object-replace)
unchanged:	Money/ExchangeRateProvider.php
Warnings for Money/ExchangeRateProvider.php:
 - Money/ExchangeRateProvider.php:136 file_get_contents(): Use new asset abstraction (https://docs.silverstripe.org/en/4/changelogs/4.0.0#asset-storage)
unchanged:	Tasks/EcommerceTaskReviewSearches.php
Warnings for Tasks/EcommerceTaskReviewSearches.php:
 - Tasks/EcommerceTaskReviewSearches.php:100 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Pages/OrderConfirmationPage.php
Warnings for Pages/OrderConfirmationPage.php:
 - Pages/OrderConfirmationPage.php:272 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Pages/OrderConfirmationPage.php:275 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Pages/OrderConfirmationPage.php:278 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Pages/OrderConfirmationPage.php:281 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
unchanged:	Pages/Product.php
Warnings for Pages/Product.php:
 - Pages/Product.php:439 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
modified:	Pages/ProductGroup.php
@@ -1821,7 +1821,7 @@
     protected function getStage()
     {
         $stage = '';
-        if (Versioned::current_stage() === 'Live') {
+        if (Versioned::get_stage() === 'Live') {
             $stage = '_Live';
         }


Warnings for Pages/ProductGroup.php:
 - Pages/ProductGroup.php:610 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Pages/ProductGroup.php:674 SilverStripe\Forms\HeaderField: Requires an explicit $name constructor argument (in addition to $title)
 - Pages/ProductGroup.php:1824 SilverStripe\Versioned\Versioned::current_stage(): Moved to SilverStripe\Versioned\Versioned::get_stage()
Writing changes for 6 files
✔✔✔