<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Config\Config;

use SilverStripe\Core\Injector\Injector;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\SS_List;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * This is a preset upload field for product images.
 *
 * In the config you can set the default folder name for a certain image Field
 * using
 *
 *
 * MyClass:
 *   folder_name_for_images: "MyFolderName"
 *
 *
 * It is recommended that you also set the calling class manually as we expect this
 * to be faster
 *
 * e.g.
 *
 *
 *     $fields->addFieldToTab('Root.Images', $uploadField = new ProductProductImageUploadField('Image', _t('Product.IMAGE', 'Product Image')));
 *     $uploadField->setCallingClass("Product");
 */
class ProductProductImageUploadField extends UploadField
{
    /**
     * @var string
     */
    protected $callingClass = '';

    /**
     * @var array Config for this field used in both, php and javascript
     *            (will be merged into the config of the javascript file upload plugin).
     *            See framework/_config/uploadfield.yml for configuration defaults and documentation.
     */
    protected $ufConfig = [
        // @var boolean
        'autoUpload' => true,
        /*
         * php validation of allowedMaxFileNumber only works when a db relation is available, set to null to allow
         * unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
         * @var int
         */
        'allowedMaxFileNumber' => 1,
        /*
         * @var boolean|string Can the user upload new files, or just select from existing files.
         * String values are interpreted as permission codes.
         */
        'canUpload' => true,
        /*
         * @var boolean|string Can the user attach files from the assets archive on the site?
         * String values are interpreted as permission codes.
         */
        'canAttachExisting' => 'CMS_ACCESS_AssetAdmin',
        /*
         * @var boolean If a second file is uploaded, should it replace the existing one rather than throwing an errror?
         * This only applies for has_one relationships, and only replaces the association
         * rather than the actual file database record or filesystem entry.
         */
        'replaceExistingFile' => true,
        // @var int
        'previewMaxWidth' => 80,
        // @var int
        'previewMaxHeight' => 60,
        /*
         * javascript template used to display uploading files
         * @see javascript/UploadField_uploadtemplate.js
         * @var string
         */
        'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',
        /*
         * javascript template used to display already uploaded files
         * @see javascript/UploadField_downloadtemplate.js
         * @var string
         */
        'downloadTemplateName' => 'ss-uploadfield-downloadtemplate',
        /*
         * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
         * @example 'getCMSFields'
         * @var FieldList|string
         */
        'fileEditFields' => null,
        /*
         * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
         * @example 'getCMSActions'
         * @var FieldList|string
         */
        'fileEditActions' => null,
        /*
         * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
         * @example 'getCMSValidator'
         * @var string
         */
        'fileEditValidator' => null,
    ];

    /**
     * Construct a new UploadField instance.
     *
     * @param string  $name  the internal field name, passed to forms
     * @param string  $title the field label
     * @param SS_List $items If no items are defined, the field will try to auto-detect an existing relation on
     *
     *                       @see $record}, with the same name as the field name.
     */
    public function __construct($name, $title = null, SS_List $items = null)
    {
        parent::__construct($name, $title, $items);
        $this->getValidator()->setAllowedExtensions(['gif', 'jpg', 'png']);

        $callingClass = $this->getCallingClass();
        $folderName = Injector::inst()->get($callingClass)->getFolderName();
        if (! $folderName) {
            $folderName = ClassInfo::shortName($callingClass) . '_' . $name;
        }
        $this->setFolderName($folderName);
    }

    /**
     * Must be a real class name.
     *
     * @param string $name
     */
    public function setCallingClass($name): self
    {
        $this->callingClass = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    protected function getCallingClass()
    {
        if ($this->callingClass) {
            return $this->callingClass;
        }
        //get the trace
        $trace = debug_backtrace();
        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];
        $traceCount = count($trace);
        // +1 to i cos we have to account for calling this function
        for ($i = 1; $i < $traceCount; ++$i) {
            if (isset($trace[$i])) {
                // is it set?
                if ($class !== $trace[$i]['class']) { // is it a different class
                    return $trace[$i]['class'];
                }
            }
        }

        return Product::class;
    }
}
