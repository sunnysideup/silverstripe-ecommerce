<?php

/**
 * Adds all members, who have bought something, to the customer group.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class AddCustomersToCustomerGroups extends BuildTask {

	protected $title = "Add Customers to Customer Group";

	protected $description = "Takes all the Members that have ordered something and adds them to the Customer Security Group.";

	function run($request) {
		$customerGroup = EcommerceRole::get_customer_group();;
		if($customerGroup) {
			$allCombos = DB::query("
				SELECT \"Group_Members\".\"ID\", \"Group_Members\".\"MemberID\", \"Group_Members\".\"GroupID\"
				FROM \"Group_Members\"
				WHERE \"Group_Members\".\"GroupID\" = ".$customerGroup->ID.";"
			);
			//make an array of all combos
			$alreadyAdded = array();
			$alreadyAdded[-1] = -1;
			if($allCombos) {
				foreach($allCombos as $combo) {
					$alreadyAdded[$combo["MemberID"]] = $combo["MemberID"];
				}
			}
			$unlistedMembers = Member::get()
				->exclude(
					array(
						"ID" => $alreadyAdded
					)
				)
				->innerJoin("Order", "\"Order\".\"MemberID\" = \"Member\".\"ID\"");
			//add combos
			if($unlistedMembers->count()) {
				$existingMembers = $customerGroup->Members();
				foreach($unlistedMembers as $member) {
					$existingMembers->add($member);
					DB::alteration_message("Added member to customers: ".$member->Email, "created");
				}
			}
		}
		else {
			DB::alteration_message("NO customer group found", "deleted");
		}
	}


}
