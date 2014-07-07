<?php


/**
 * create the e-commerce specific Member Groups
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class CreateEcommerceMemberGroups extends BuildTask{

	protected $title = "Create E-commerce Member Groups";

	protected $description = "Create the member groups and members for e-commerce, such as Customers and Shop Admininistrators.";

	function run($request){
		$this->CreateGroup(
			$code = EcommerceConfig::get("EcommerceRole", "customer_group_code"),
			$name = EcommerceConfig::get("EcommerceRole", "customer_group_name"),
			$parentGroup = null,
			$permissionCode = EcommerceConfig::get("EcommerceRole", "customer_permission_code"),
			$roleTitle = "",
			$permissionArray = array(),
			$member = null
		);

		$this->CreateGroup(
			$code = EcommerceConfig::get("EcommerceRole", "admin_group_code"),
			$name = EcommerceConfig::get("EcommerceRole", "admin_group_name"),
			$parentGroup = null,
			$permissionCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code"),
			$roleTitle = EcommerceConfig::get("EcommerceRole", "admin_role_title"),
			$permissionArray = EcommerceConfig::get("EcommerceRole", "admin_role_permission_codes"),
			$member = null
		);
	}


	/**
	 * set up a group with permissions, roles, etc...
	 * @param String $code code for the group
	 * @param String $name title for the group
	 * @param Group $parentGroup group object that is the parent of the group
	 * @param String $permissionCode Permission Code for the group (e.g. CMS_DO_THIS_OR_THAT)
	 * @param String $roleTitle Role Title - e.g. Store Manager
	 * @param Array $permissionArray Permission Array - list of permission codes applied to the group
	 * @param Member $member Default Member added to the group (e.g. sales@mysite.co.nz)
	 *
	 */
	public function CreateGroup($code, $name, $parentGroup = null, $permissionCode = "", $roleTitle = "", $permissionArray = array(), $member = null) {
		$group = Group::get()->filter(array("Code" => $code))->first();
		if(!$group) {
			$group = new Group();
			$group->Code = $code;
		}
		$group->Title = $name;
		if($parentGroup) {
			$group->ParentID = $parentGroup->ID;
		}
		$group->write();
		$doubleGroups = Group::get()->filter(array("Title" => $name))->exclude(array("ID" => $group->ID));
		if($doubleGroups->count()) {
			$realMembers = $group->Members();
			foreach($doubleGroups as $doubleGroup) {
				$fakeMembers = $doubleGroup->Members();
				foreach($fakeMembers as $fakeMember) {
					DB::alteration_message("adding customers: ".$fakeMember->Email, "created");
					$realMembers->add($fakeMember);
				}
				$doubleGroup->delete();
				DB::alteration_message("deleting double group ", "deleted");
			}
		}
		if($permissionCode) {
			if(DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '".$group->ID."' AND \"Code\" LIKE '".$permissionCode."'")->numRecords() == 0 ) {
				Permission::grant($group->ID, $permissionCode);
				DB::alteration_message($name.' permissions granted',"created");
			}
			else {
				DB::alteration_message($name." permissions already granted","created");
			}
		}
		if($roleTitle) {
			$permissionRole = PermissionRole::get()
				->Filter(array("Title" => $roleTitle))
				->First();
			if($permissionRole) {
				//do nothing
				DB::alteration_message($name.' role in place',"created");
			}
			else {
				$permissionRole = new PermissionRole();
				$permissionRole->Title = $roleTitle;
				$permissionRole->OnlyAdminCanApply = true;
				$permissionRole->write();
				DB::alteration_message($name.' role created',"created");
			}
			if($permissionRole) {
				if(is_array($permissionArray) && count($permissionArray)) {
					foreach($permissionArray as $permissionCode) {
						$permissionRoleCode = PermissionRoleCode::get()
							->Filter(array("Code" => $permissionCode))
							->First();
						if($permissionRoleCode) {
							//do nothing
						}
						else {
							$permissionRoleCode = new PermissionRoleCode();
							$permissionRoleCode->Code = $permissionCode;
							$permissionRoleCode->RoleID = $permissionRole->ID;
							$permissionRoleCode->write();
						}
					}
				}
				if($group) {
					$existingGroups = $permissionRole->Groups();
					$existingGroups->add($group);
				}
			}
		}
		if($member) {
			$member->groups()->add($group);
			DB::alteration_message(" adding member ".$member->Email." to group ".$group->Title,"created");
		}
	}


}
