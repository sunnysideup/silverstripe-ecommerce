<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Adds all members, who have bought something, to the customer group.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskAddCustomersToCustomerGroups extends BuildTask
{
    protected static string $commandName = 'ecommerce:add-customers-to-groups';

    protected string $title = 'Add Customers to Customer Group';

    protected static string $description = 'Takes all the Members that have ordered something and adds them to the Customer Security Group.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $customerGroup = EcommerceRole::get_customer_group();
        if ($customerGroup) {
            $allCombos = DB::query(
                '
                SELECT "Group_Members"."ID", "Group_Members"."MemberID", "Group_Members"."GroupID"
                FROM "Group_Members"
                WHERE "Group_Members"."GroupID" = ' . $customerGroup->ID . ';'
            );
            //make an array of all combos
            $alreadyAdded = [];
            $alreadyAdded[-1] = -1;
            if ($allCombos) {
                foreach ($allCombos as $combo) {
                    $alreadyAdded[$combo['MemberID']] = $combo['MemberID'];
                }
            }

            $unlistedMembers = Member::get()
                ->exclude(
                    [
                        'ID' => $alreadyAdded,
                    ]
                )
                ->innerJoin('Order', '"Order"."MemberID" = "Member"."ID"')
            ;
            //add combos
            if ($unlistedMembers->exists()) {
                $existingMembers = $customerGroup->Members();
                foreach ($unlistedMembers as $member) {
                    $existingMembers->add($member);
                    $output->writeln('Added member to customers: ' . $member->Email);
                }
            }
        } else {
            $output->writeln('NO customer group found');
        }

        return Command::SUCCESS;
    }
}
