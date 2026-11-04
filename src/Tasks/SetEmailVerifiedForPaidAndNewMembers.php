<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\DestructiveTaskGuard;

/**
 * Sets "EmailVerifiedAt" for members who have not verified their email address yet, but who
 * - have an order with a successful payment: the date of their most recent successful payment, or
 * - created their account in the last few days (default 3): the date they created the account.
 * A paid order wins over the creation date.
 *
 * Members with any of the permission codes in config excluded_permission_codes (default: ADMIN)
 * are never changed, including members of child groups of an admin group.
 *
 * It only sets the date: it does NOT remove passwords (see EcommerceRole::markEmailAsVerified).
 * Members that already have a date are never changed.
 *
 * It only runs when less than 1% of members is verified (config max_percentage_verified).
 *
 * Options: ?days=3 (how recent an account has to be), ?dryrun=1 (only count).
 */
class SetEmailVerifiedForPaidAndNewMembers extends BuildTask
{
    protected $title = 'Set email verified date for members with a paid order or a new account';

    protected $description = 'Members without a verified date get the date of their last successful payment, or (for accounts of the last few days) their creation date. Admins are skipped. ?days=3&dryrun=1';

    /**
     * The task only runs if less than this percentage of all members is verified.
     *
     * @var float
     */
    private static $max_percentage_verified = 1;

    /**
     * Members with any of these permission codes are left alone.
     *
     * @var string[]
     */
    private static $excluded_permission_codes = [
        'ADMIN',
    ];

    private static $segment = 'set-email-verified-for-paid-and-new-members';

    public function run($request)
    {
        $days = max(1, min(3, (int) ($request->requestVar('days') ?: 3)));
        $dryRun = (bool) $request->requestVar('dryrun');

        // Safety: this is a one-off to start off the verified dates. Once real verifications are coming in
        // (people logging in with a code), it must not run again.
        $totalMembers = (int) DB::query('SELECT COUNT(*) FROM "Member"')->value();
        $verifiedMembers = (int) DB::query('SELECT COUNT(*) FROM "Member" WHERE "EmailVerifiedAt" IS NOT NULL')->value();
        $maxPercentage = (float) static::config()->get('max_percentage_verified');
        $percentage = $totalMembers > 0 ? ($verifiedMembers / $totalMembers) * 100 : 0;
        DB::alteration_message('Verified members: ' . $verifiedMembers . ' of ' . $totalMembers . ' (' . round($percentage, 2) . '%)');
        if ($percentage >= $maxPercentage) {
            DB::alteration_message('Not run: this task only runs when less than ' . $maxPercentage . '% of members are verified.', 'error');

            return;
        }

        if (! DestructiveTaskGuard::mayRun($request, $this, 'Sets the email verified date for members with a successful payment, and for accounts created in the last ' . $days . ' days (admins excluded).')) {
            return;
        }

        // admins (and anyone else with an excluded permission code)
        $excludedIDs = $this->getExcludedMemberIDs();
        DB::alteration_message('Excluded (admin) members: ' . count($excludedIDs));
        $excludeAliased = $this->notInSql('m."ID"', $excludedIDs);
        $excludePlain = $this->notInSql('"ID"', $excludedIDs);

        $paidJoin = '
            INNER JOIN (
                SELECT o."MemberID" AS "MemberID", MAX(p."Created") AS "LastPaid"
                FROM "EcommercePayment" AS p
                INNER JOIN "Order" AS o ON o."ID" = p."OrderID"
                WHERE p."Status" = \'Success\' AND o."MemberID" > 0
                GROUP BY o."MemberID"
            ) AS paid ON paid."MemberID" = m."ID"';

        $countPaid = (int) DB::query(
            'SELECT COUNT(*) FROM "Member" AS m ' . $paidJoin . ' WHERE m."EmailVerifiedAt" IS NULL' . $excludeAliased
        )->value();
        DB::alteration_message('Members with a successful payment and no verified date: ' . $countPaid);
        if (! $dryRun) {
            DB::query(
                'UPDATE "Member" AS m ' . $paidJoin . '
                SET m."EmailVerifiedAt" = paid."LastPaid"
                WHERE m."EmailVerifiedAt" IS NULL' . $excludeAliased
            );
            DB::alteration_message('Updated (paid): ' . DB::affected_rows(), 'created');
        }

        // runs after the paid update, so only the members that are still without a date are left.
        $since = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $countNew = (int) DB::prepared_query(
            'SELECT COUNT(*) FROM "Member" WHERE "EmailVerifiedAt" IS NULL AND "Created" >= ?' . $excludePlain,
            [$since]
        )->value();
        DB::alteration_message('Accounts created since ' . $since . ' and no verified date' . ($dryRun ? ' (not counting the paid ones above)' : '') . ': ' . $countNew);
        if (! $dryRun) {
            DB::prepared_query(
                'UPDATE "Member" SET "EmailVerifiedAt" = "Created" WHERE "EmailVerifiedAt" IS NULL AND "Created" >= ?' . $excludePlain,
                [$since]
            );
            DB::alteration_message('Updated (new accounts): ' . DB::affected_rows(), 'created');
        } else {
            DB::alteration_message('Dry run: nothing has been changed.');
        }
    }

    /**
     * IDs of all members that have one of the excluded permission codes,
     * through their groups (incl. child groups) or roles.
     *
     * @return int[]
     */
    protected function getExcludedMemberIDs(): array
    {
        $codes = array_filter((array) static::config()->get('excluded_permission_codes'));
        if ($codes === []) {
            return [];
        }
        $ids = Permission::get_members_by_permission($codes)->column('ID');

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * @param int[] $ids
     */
    protected function notInSql(string $field, array $ids): string
    {
        return $ids === [] ? '' : ' AND ' . $field . ' NOT IN (' . implode(',', $ids) . ')';
    }
}
