<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;

/**
 * Lets you search the payments by type (the ClassName).
 *
 * The options come from the payments that are actually in the database (and not from the classes that are installed),
 * because older payments are often of a type that is no longer installed (e.g. a payment provider that was replaced).
 * Those would be missing otherwise.
 * The options are only looked up when the field is shown, never when the configuration is loaded.
 *
 * Used in the searchable_fields of EcommercePayment.
 */
class EcommercePaymentTypeDropdownField extends DropdownField
{
    /**
     * @var null|array<string, string>
     */
    protected static $types_cache;

    public function __construct($name, $title = null, $source = [], $value = null)
    {
        parent::__construct($name, $title, $source, $value);
        $this->setEmptyString(_t('EcommercePaymentTypeDropdownField.ANY_TYPE', '(any type)'));
    }

    public function getSource()
    {
        if ([] === $this->source) {
            $this->source = self::payment_types();
        }

        return $this->source;
    }

    /**
     * @return array<string, string> ClassName => label (with the number of payments)
     */
    public static function payment_types(): array
    {
        if (null === self::$types_cache) {
            $options = [];
            $table = DataObject::getSchema()->tableName(EcommercePayment::class);
            $rows = DB::query('SELECT "ClassName", COUNT(*) AS "Count" FROM "' . $table . '" GROUP BY "ClassName"');
            foreach ($rows as $row) {
                $className = (string) $row['ClassName'];
                if ('' === $className) {
                    continue;
                }

                $parts = explode('\\', $className);
                $shortName = (string) end($parts);
                $name = $shortName;
                if (ClassInfo::exists($className) && is_a($className, EcommercePayment::class, true)) {
                    $name = (string) singleton($className)->i18n_singular_name();
                    if ($name !== $shortName) {
                        $name .= ' - ' . $shortName;
                    }
                } else {
                    $name .= ' ' . _t('EcommercePaymentTypeDropdownField.NOT_INSTALLED', '(no longer installed)');
                }

                $options[$className] = $name . ' (' . number_format((int) $row['Count']) . ')';
            }

            asort($options);
            self::$types_cache = $options;
        }

        return self::$types_cache;
    }
}
