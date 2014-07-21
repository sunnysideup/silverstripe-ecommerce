<?php

/**
 *
 *
 *
 */

class Product_ProductImageUploadField extends UploadField {

	function getRelationAutosetClass($default ='File'){
		return "Image";
	}

	/**
	 * @var array Config for this field used in both, php and javascript
	 * (will be merged into the config of the javascript file upload plugin).
	 * See framework/_config/uploadfield.yml for configuration defaults and documentation.
	 */
	protected $ufConfig = array(
		/**
		 * @var boolean
		 */
		'autoUpload' => true,
		/**
		 * php validation of allowedMaxFileNumber only works when a db relation is available, set to null to allow
		 * unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
		 * @var int
		 */
		'allowedMaxFileNumber' => 1,
		/**
		 * @var boolean|string Can the user upload new files, or just select from existing files.
		 * String values are interpreted as permission codes.
		 */
		'canUpload' => true,
		/**
		 * @var boolean|string Can the user attach files from the assets archive on the site?
		 * String values are interpreted as permission codes.
		 */
		'canAttachExisting' => "CMS_ACCESS_AssetAdmin",
		/**
		 * @var boolean If a second file is uploaded, should it replace the existing one rather than throwing an errror?
		 * This only applies for has_one relationships, and only replaces the association
		 * rather than the actual file database record or filesystem entry.
		 */
		'replaceExistingFile' => true,
		/**
		 * @var int
		 */
		'previewMaxWidth' => 80,
		/**
		 * @var int
		 */
		'previewMaxHeight' => 60,
		/**
		 * javascript template used to display uploading files
		 * @see javascript/UploadField_uploadtemplate.js
		 * @var string
		 */
		'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',
		/**
		 * javascript template used to display already uploaded files
		 * @see javascript/UploadField_downloadtemplate.js
		 * @var string
		 */
		'downloadTemplateName' => 'ss-uploadfield-downloadtemplate',
		/**
		 * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
		 * @example 'getCMSFields'
		 * @var FieldList|string
		 */
		'fileEditFields' => null,
		/**
		 * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
		 * @example 'getCMSActions'
		 * @var FieldList|string
		 */
		'fileEditActions' => null,
		/**
		 * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
		 * @example 'getCMSValidator'
		 * @var string
		 */
		'fileEditValidator' => null
	);

}

