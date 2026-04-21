# Upgrade to Silverstripe CMS 6

This document outlines the changes required to upgrade a project using the `sunnysideup/ecommerce` module from Silverstripe CMS 5 to Silverstripe CMS 6.

## Requirements & Dependencies

*   **Silverstripe CMS:** Upgrade `silverstripe/cms` to `^6.0`.
*   **Lumberjack:** Upgrade `silverstripe/lumberjack` to `^4.1`.
*   **SortableFile:** Upgrade `bummzack/sortablefile` to `^3.0`.
*   **PHP Version:** This module now utilizes PHP 8.2+ features, including constructor promotion and the `#[Override]` attribute.
*   **⚠️ Missing Sub-modules:** Several Sunnyside Up sub-modules have been removed from `require` as they do not yet have stable SS6 releases. If your project relies on these, you must manually check their compatibility:
    *   `google_address_field`, `phone_field`, `cms_edit_link_field`, `vardump`, `ajax-select-field`, and others.

## Architectural Changes

### BuildTasks and CLI Commands ⚠️
All `BuildTask` classes have been refactored to use the Symfony Console integration introduced in Silverstripe CMS 6.
*   Replace `run($request)` with `execute(InputInterface $input, PolyOutput $output)`.
*   Replace `DB::alteration_message($msg, $type)` with `$output->writeln($msg)` or `$output->writeForHtml($msg)`.
*   Tasks now define a static `$commandName` (e.g., `ecommerce:cart-cleanup`).
*   Define task arguments and options using the `getOptions()` method instead of checking `$_GET`.

### ModelData and ViewableData
*   Several internal API classes (like `EcommerceConfigAjaxDefinitions`) now extend `SilverStripe\Model\ModelData` instead of `SilverStripe\View\ViewableData`.

## CMS & Field Scaffolding

### New Scaffolding Configuration 🔍
The module now uses a custom configuration property to manage automated field scaffolding in `getCMSFields()`.
*   **Note:** If you have overridden `getCMSFields()` in `Product`, `ProductGroup`, or `Order` subclasses, check the `scaffold_cms_fields_settings` private static array. 
*   Fields previously removed manually via `removeByName()` may now be ignored globally via the `ignoreFields` key in this config.

### CMS Page Add Controller ⚠️
*   `SilverStripe\CMS\Controllers\CMSPageAddController` is deprecated/removed in SS6.
*   `CMSPageAddControllerProducts::PageTypes()` has been replaced/renamed to `RecordTypes()`.

## API & Namespace Changes

### Namespaces Moved
*   `SilverStripe\ORM\ArrayList` → `SilverStripe\Model\List\ArrayList`
*   `SilverStripe\ORM\SS_List` → `SilverStripe\Model\List\SS_List`
*   `SilverStripe\ORM\PaginatedList` → `SilverStripe\Model\List\PaginatedList`
*   `SilverStripe\View\ArrayData` → `SilverStripe\Model\ArrayData`

### Validation
*   `SilverStripe\Forms\RequiredFields` → `SilverStripe\Forms\Validation\RequiredFieldsValidator`.
*   Custom validators (e.g., `OrderFormAddressValidator`) now inherit from `RequiredFieldsValidator`.

### DataObject Methods
*   Replace calls to `DataObject::get_one($class, $filter)` with `$class::get()->filter($filter)->first()`.
*   Replace `i18n_plural_name()` overrides with `plural_name()`.

## Database & Model Changes

### Property Promotion
*   Internal API classes like `GetParentDetails` now use PHP 8 constructor promotion. If you are extending these constructors, ensure you match the new signature.

### Table Names 🔍
*   **Note:** Ensure all DataObjects define a `private static string $table_name`. Most ecommerce models have been updated to be explicit.

## Removed Features

*   **Legacy Redirects:** Removed old SS3/SS4 style redirect handlers in `ShoppingCartController`.
*   **Old Field Types:** `BuyableFieldType` has been updated to strictly return `BuyableSelectField`.
