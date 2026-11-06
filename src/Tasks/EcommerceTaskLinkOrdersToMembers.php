<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Api\DestructiveTaskGuard;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * Every submitted order has a valid email address and a member.
 * Older orders (guest orders) may not have a member. This task reports on them (health check)
 * and, when asked to, links them to a member:
 *
 * - there is no member with the email address of the order: a member (without a password) is created.
 * - there is a member with that email address, and they have verified their email address (EmailVerifiedAt):
 *   the order is linked to them (this is what happens now when they log in, see
 *   EcommerceRole::claimGuestOrdersForVerifiedEmail).
 * - there is a member with that email address, but they have not verified it:
 *   the order is linked to them but held (Order.EmailNotVerified) until they verify their email address.
 *
 * The orders themselves are not changed in any other way (not even the LastEdited date).
 *
 * Usage:
 * - /dev/tasks/EcommerceTaskLinkOrdersToMembers                    : report only (nothing is changed)
 * - /dev/tasks/EcommerceTaskLinkOrdersToMembers?fix=1&limit=500    : link up to 500 orders (run it again until done)
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskLinkOrdersToMembers extends BuildTask
{
    protected $title = 'Link submitted orders without a member to a member (report only, unless fix=1)';

    protected $description = '
        Reports on submitted orders without a member and without a valid billing email address.
        Add ?fix=1 (and optionally &limit=500) to link orders without a member to a member (using the email address of the order).
        Without fix=1 nothing is changed.';

    private static $segment = 'EcommerceTaskLinkOrdersToMembers';

    private static int $default_limit = 500;

    /**
     * members that this run has created: all their orders are theirs (so they are not held).
     *
     * @var array<int, bool>
     */
    protected array $createdMemberIDs = [];

    public function run($request)
    {
        $fix = (bool) $request->requestVar('fix');
        $limit = (int) $request->requestVar('limit');
        if ($limit < 1) {
            $limit = (int) static::config()->get('default_limit');
        }
        if ($fix && ! DestructiveTaskGuard::mayRun($request, $this, 'Links submitted orders that have no member to a member (and creates members for them). The orders are changed (member only).', DestructiveTaskGuard::ANYWHERE)) {
            return;
        }
        set_time_limit(1200);

        $this->reportOnOrdersWithMember();

        $orderIDs = Order::get_datalist_of_orders_with_submit_record(true, true)
            ->filter(['MemberID' => 0])
            ->sort(['ID' => 'ASC'])
            ->column('ID');
        DB::alteration_message('Submitted orders without a member: ' . count($orderIDs), count($orderIDs) ? 'deleted' : 'created');

        $stats = [
            'linked to a new member (created)' => 0,
            'linked to an existing member who has verified their email address' => 0,
            'linked to an existing member, held until they verify their email address' => 0,
            'can not be linked: no valid billing email address' => 0,
            'can not be linked: other problem' => 0,
        ];
        $notLinkable = [];
        $emailsOfNewMembers = [];
        $done = 0;
        foreach ($orderIDs as $orderID) {
            if ($fix && $done >= $limit) {
                DB::alteration_message('Stopped after ' . $limit . ' orders. Run this task again for the remainder.', 'changed');

                break;
            }
            $order = Order::get_by_id((int) $orderID);
            if (! $order || ! $order->exists()) {
                continue;
            }
            $billingAddress = $order->BillingAddressID ? $order->BillingAddress() : null;
            $email = $billingAddress && $billingAddress->exists() ? trim((string) $billingAddress->Email) : '';
            if ('' === $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                ++$stats['can not be linked: no valid billing email address'];
                $notLinkable[] = $order->ID;

                continue;
            }
            $existing = EcommerceRole::find_member_by_email($email);
            $key = mb_strtolower($email);

            if (! $fix) {
                // report only
                if ($existing) {
                    if ($existing->EmailVerifiedAt) {
                        ++$stats['linked to an existing member who has verified their email address'];
                    } else {
                        ++$stats['linked to an existing member, held until they verify their email address'];
                    }
                } else {
                    // the first order of an email address creates the member, the other orders are linked to that member.
                    ++$stats['linked to a new member (created)'];
                    $emailsOfNewMembers[$key] = true;
                }

                continue;
            }

            $member = EcommerceRole::attach_member_to_guest_order($order, true);
            if (! $member || ! $order->MemberID) {
                ++$stats['can not be linked: other problem'];
                $notLinkable[] = $order->ID;

                continue;
            }
            if (! $existing) {
                $this->createdMemberIDs[(int) $member->ID] = true;
            }
            if (isset($this->createdMemberIDs[(int) $member->ID])) {
                // the member was created by this task for this email address: it is their order.
                $order->EmailNotVerified = false;
                ++$stats['linked to a new member (created)'];
            } elseif ($order->EmailNotVerified) {
                ++$stats['linked to an existing member, held until they verify their email address'];
            } else {
                ++$stats['linked to an existing member who has verified their email address'];
            }
            // direct update: we do not want to change anything else about the (submitted) order,
            // not even the LastEdited date.
            DB::prepared_query(
                'UPDATE "Order" SET "MemberID" = ?, "EmailNotVerified" = ? WHERE "ID" = ? AND "MemberID" = 0',
                [(int) $order->MemberID, $order->EmailNotVerified ? 1 : 0, (int) $order->ID]
            );
            ++$done;
        }

        if (! $fix) {
            DB::alteration_message('Members that would be created (one per new email address): ' . count($emailsOfNewMembers));
        }
        DB::alteration_message($fix ? 'RESULT (orders changed):' : 'REPORT (nothing has been changed - add ?fix=1 to link the orders):', 'created');
        foreach ($stats as $label => $number) {
            DB::alteration_message(Convert::raw2xml($label) . ': ' . $number, $number && str_starts_with($label, 'can not') ? 'deleted' : 'created');
        }
        if ([] !== $notLinkable) {
            DB::alteration_message('Orders that can not be linked (fix these by hand in the CMS): #' . implode(', #', array_slice($notLinkable, 0, 200)) . (count($notLinkable) > 200 ? ' ...' : ''), 'deleted');
        }
    }

    /**
     * Health check: submitted orders that have a member, but no valid email address at all.
     */
    protected function reportOnOrdersWithMember(): void
    {
        $noEmail = [];
        $orderIDs = Order::get_datalist_of_orders_with_submit_record(true, true)
            ->filter(['MemberID:GreaterThan' => 0])
            ->column('ID');
        foreach (array_chunk($orderIDs, 500) as $chunk) {
            foreach (Order::get()->filter(['ID' => $chunk]) as $order) {
                if ('' === $order->getValidCustomerEmail()) {
                    $noEmail[] = $order->ID;
                }
            }
        }
        $heldCount = Order::get()->filter(['EmailNotVerified' => 1])->count();
        DB::alteration_message('Submitted orders with a member but without any valid email address: ' . count($noEmail) . ([] !== $noEmail ? ' (#' . implode(', #', array_slice($noEmail, 0, 200)) . ')' : ''), [] === $noEmail ? 'created' : 'deleted');
        DB::alteration_message('Orders held until the email address of the member is verified: ' . $heldCount);
    }
}
