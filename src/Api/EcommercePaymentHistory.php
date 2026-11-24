<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Convert;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;

/**
 * Shows the history of a payment (every time it was saved) as HTML for the CMS:
 * when, who and what changed compared to the version before.
 *
 * EcommercePayment keeps a version every time it is saved (Versioned, without draft / live).
 *
 * Good to know:
 * - only saves are recorded: changes made directly in the database are not, and neither are edits that were refused;
 * - "who" is the user that was logged in when the payment was saved: nobody logged in means the customer
 *   (not logged in), the payment provider (e.g. a callback) or a task;
 * - the history starts when versioning was switched on.
 */
class EcommercePaymentHistory
{
    /**
     * Columns that are about the version itself, or that change with every save.
     */
    private const IGNORE_FIELDS = [
        'ID',
        'RecordID',
        'Version',
        'Created',
        'LastEdited',
        'AuthorID',
        'PublisherID',
        'WasPublished',
        'WasDraft',
        'WasDeleted',
        'ClassName',
    ];

    private const MAX_LENGTH = 300;

    public static function html(EcommercePayment $payment): string
    {
        if (! $payment->exists()) {
            return '';
        }

        if (! $payment->hasExtension(Versioned::class)) {
            return '<p>Versioning is not switched on for payments.</p>';
        }

        // newest first, we need the oldest first to see what changed.
        $versions = array_reverse(iterator_to_array($payment->Versions(), false));
        if ([] === $versions) {
            return '<p>No version has been recorded for this payment yet. A version is recorded the next time it is saved.</p>';
        }

        $rows = [];
        $previous = null;
        foreach ($versions as $version) {
            $rows[] = self::row($payment, $version->toMap(), $previous);
            $previous = $version->toMap();
        }

        return '<p>' . Convert::raw2xml(
            'A version is kept every time this payment is saved. Who = the user that was logged in at that moment; '
            . '"nobody logged in" is the customer (not logged in), the payment provider or a task. '
            . 'Changes made directly in the database, and edits that were refused, are not recorded. '
            . 'The history starts when versioning was switched on.'
        ) . '</p>'
            . '<table class="table table-striped" style="width: 100%;">'
            . '<thead><tr><th>Version</th><th>When</th><th>Who</th><th>What</th></tr></thead>'
            . '<tbody>' . implode('', array_reverse($rows)) . '</tbody>'
            . '</table>';
    }

    /**
     * @param array<string, mixed>      $current
     * @param null|array<string, mixed> $previous
     */
    protected static function row(EcommercePayment $payment, array $current, ?array $previous): string
    {
        $what = null === $previous ? self::firstVersion($payment, $current) : self::changes($payment, $current, $previous);

        return '<tr>'
            . '<td>' . (int) ($current['Version'] ?? 0) . '</td>'
            . '<td>' . Convert::raw2xml((string) ($current['LastEdited'] ?? '')) . '</td>'
            . '<td>' . self::author((int) ($current['AuthorID'] ?? 0)) . '</td>'
            . '<td>' . $what . '</td>'
            . '</tr>';
    }

    /**
     * The first version we have: all the values (there is nothing to compare with).
     *
     * @param array<string, mixed> $current
     */
    protected static function firstVersion(EcommercePayment $payment, array $current): string
    {
        $lines = [];
        foreach ($current as $field => $value) {
            if (in_array($field, self::IGNORE_FIELDS, true) || '' === trim((string) $value)) {
                continue;
            }

            $lines[] = '<strong>' . Convert::raw2xml($payment->fieldLabel($field)) . '</strong>: ' . self::value($value);
        }

        return '<em>First recorded version.</em><br />' . implode('<br />', $lines);
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $previous
     */
    protected static function changes(EcommercePayment $payment, array $current, array $previous): string
    {
        $lines = [];
        foreach (array_unique(array_merge(array_keys($current), array_keys($previous))) as $field) {
            if (in_array($field, self::IGNORE_FIELDS, true)) {
                continue;
            }

            $old = (string) ($previous[$field] ?? '');
            $new = (string) ($current[$field] ?? '');
            if ($old !== $new) {
                $lines[] = '<strong>' . Convert::raw2xml($payment->fieldLabel($field)) . '</strong>: '
                    . self::value($old) . ' &rarr; ' . self::value($new);
            }
        }

        return [] === $lines ? '<em>Saved without changing anything.</em>' : implode('<br />', $lines);
    }

    protected static function author(int $memberID): string
    {
        if ($memberID < 1) {
            return 'nobody logged in';
        }

        $member = Member::get_by_id($memberID);
        if (! $member) {
            return 'member #' . $memberID . ' (no longer exists)';
        }

        $label = trim((string) $member->getTitle());
        if ('' !== (string) $member->Email) {
            $label .= ' (' . $member->Email . ')';
        }

        $isStaff = EcommerceRole::current_member_can_process_orders($member);

        return Convert::raw2xml($label) . ' ' . ($isStaff ? '<strong>[shop staff]</strong>' : '[not shop staff]');
    }

    /**
     * Readable and safe: no HTML, not too long, "(empty)" for nothing.
     *
     * @param mixed $value
     */
    protected static function value($value): string
    {
        $text = trim(strip_tags((string) $value));
        if ('' === $text) {
            return '<em>(empty)</em>';
        }

        if (mb_strlen($text) > self::MAX_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_LENGTH) . '...';
        }

        return Convert::raw2xml($text);
    }
}
