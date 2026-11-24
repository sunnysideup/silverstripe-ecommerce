# Changelog

## [Unreleased] — from 2c9f6d05 to 762a8dc4 (2026-11-24)

### Added
- **Order archiving**: orders older than a configurable cut-off (default 3 months) can be moved straight to the Archived step from the sales admin screens, without running order steps, sending emails or touching other data. New "Sales by Day" admin screen (one tab per recent day, plus older-order tabs) with an archive button.
- **Guest checkout now always has a customer**: every submitted order is linked to a member. Guests get a password-less account; orders placed with an existing customer's email are held (`EmailNotVerified`) until that customer verifies by one-time code. Members get an `EmailVerifiedAt` date, and verified members have matching guest orders claimed automatically.
- **Payment attempt order step**: orders wait until a payment attempt exists, and are cancelled after 30 days (configurable) with none.
- **Payment history and search**: payments are now versioned, with a History tab in the CMS showing who changed what and when. Payments can be searched by amount, payer name/email and payment type. A payer is worked out from the payment, the order's member or billing email.
- **Product specifications** (spec groups and typed spec values) with helpers to set them on products.
- **Multiple product group filters** at once (OR logic). Combinations are sent by POST (and kept in history state) so robots can't crawl them; optional client-side list mode for small lists.
- Adjacent pagination links (previous/next), ListNote on product groups, GET variables for product collections.
- Pending-payment handling: configurable pending message, pending amounts count as outstanding, and "disabled but displayed" payment methods.
- Better customer summaries (type of customer, paid-order count, held orders) in the CMS and exports; richer CSV export fields (courier/delivery details); order export filtered by product; excluded-members helper for order stats.
- New tasks: link old orders to members (report-only unless `fix=1`), and set email-verified dates for paid/new members.
- Rate limiting for repeated "email already has an account" checks, order-email resends and search history entries; minimum password length enforced on password fields.
- Optional SecurityID in cart links; custom event dispatched when AJAX updates the DOM; optional SRI hash for the CDN jQuery.

### Changed
- **Cart actions are now POST forms with a CSRF token** (add/remove/set quantity, remove address, etc.); GET links for data-changing actions (log out, login-as, remove from sale, start/save/delete/load order, place order for member) show a confirmation page first. Quantity field and payment-method JavaScript rewritten without jQuery.
- Order retrieve links expire (default 1 day after placement, previously 3 days from last edit) and redirect to a token-free URL.
- Password changes and email changes on the account page require the current password; passwords are no longer SQL-escaped before hashing.
- Orders unsubmitted by the checkout address form no longer attach or autofill data from existing members unless logged in as that member.
- Product page caching, search (now prepared queries, sanitiser allows more characters), order-queue processing (round-robin, stale locks recovered) and sorting of files/images improved.
- Order email, address and info-table templates restructured (`.information-table` replaces `#InformationTable`/`.infotable`; some headings changed h3→h2); `UseParentImage` field restored on products.
- Sunnysideup dependencies aligned with SS5 branches.

### Removed
- `EcommerceTemplateTest` controller, route, task and template.
- "Log out" option on the order page; submitting orders from the CMS; Firesphere magic login (replaced by one-time-code authentication); old docs moved to `docs/en/OldNotes`.

### Fixed
- Floating-point handling in `IsPaid`, null/non-numeric amount TypeErrors, SQL error on single-letter searches, double escaping in many places, wrong page numbers, log-out button, cache-key reserved characters, `GetParentDetails` conditions, `OrderFormAddress` member-difference check, validators now truly fail when extensions return false.

### Security
- Many hardening changes: CSRF on cart and destructive actions, open-redirect checks, class-name/ID inputs validated, `unserialize` without objects, SQL prepared queries, output escaping in CMS pages, sandboxed email preview (CSP), permission checks on buyable search, order lookups and email views, real client IP handling, card number/CVV never saved (masked after processing), destructive dev tasks require confirmation and can be restricted from live.

### Dependencies
- Updated: `pelago/emogrifier` ^8.0@stable → ^8.1.
- Changed to `5.x-dev` branches (from `dev-master`): google_address_field, phone_field, cms_edit_link_field, permission_provider, vardump, model-admin-many-tabs, email_address_database_field, silverstripe-ajax-select-field, yes-no-any-filter.
- Added: `sunnysideup/perfect_cms_images`, `sunnysideup/branded-login-screen`, `sunnysideup/one-time-code-authentication`.
- Removed: `firesphere/magiclogin`.
- No lock-file delta supplied; no major version bumps identified.

### ⚠️ Review carefully
- **Database schema**: new `EmailNotVerified` on Order, `EmailVerifiedAt` on Member, payment versioning tables, new `IP/Created` index on payments, spec tables, wider `OrderModifier.Name`, `OrderPaymentPendingMessage`, product `UseParentImage`; run dev/build.
- **Data-changing behaviour**: guest orders now create members; the first verification can clear an existing password (`clear_password_when_email_is_first_verified`) and ends other login sessions; archiving uses direct SQL updates; new tasks update orders/members in bulk.
- **Auth/permissions**: CSRF now enforced on cart actions, so cached pages or custom templates/JS using GET links will break; removed Firesphere magic login; account email/password changes need current password.
- **Payments**: card data no longer stored; payment-method validation restricted to supported methods; pending payments now count against outstanding; new payment-attempt step cancels orders after 30 days.
- **Emails**: retrieve-link expiry shortened; held orders hidden from customers; resend limits.
- **Removed/renamed APIs**: `SendLoginTokin` file, `EcommerceTemplateTest`, `checkCode` signature, `Convert::raw2sql` dropped from `GetVariables`, CSS/IDs renamed, reports files renamed, `ecommercetemplatetest` route.
- **Config**: `canPurchase` no longer checks visitor country; `composer.json` dependency changes.


To save time and speed up development, we do not maintain a change log.

Please see commit log on github for changes or contact Sunny Side Up for more information.
