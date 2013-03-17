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
		$customerGroup = EcommerceRole::get_customer_group();
		$customerPermissionCode = EcommerceConfig::get("EcommerceRole", "customer_permission_code");
		if(!$customerGroup) {
			$customerGroup = new Group();
			$customerGroup->Code = EcommerceConfig::get("EcommerceRole", "customer_group_code");
			$customerGroup->Title = EcommerceConfig::get("EcommerceRole", "customer_group_name");
			$customerGroup->write();
			Permission::grant( $customerGroup->ID, $customerPermissionCode);
			DB::alteration_message(EcommerceConfig::get("EcommerceRole", "customer_group_name").' Group created',"created");
		}
		elseif(DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '".$customerGroup->ID."' AND \"Code\" LIKE '".$customerPermissionCode."'")->numRecords() == 0 ) {
			Permission::grant($customerGroup->ID, $customerPermissionCode);
			DB::alteration_message(EcommerceConfig::get("EcommerceRole", "customer_group_name").' permissions granted',"created");
		}
		$customerGroup = EcommerceRole::get_customer_group();
		if(!$customerGroup) {
			user_error("could not create user group", "deleted");
		}
		else {
			DB::alteration_message(EcommerceConfig::get("EcommerceRole", "customer_group_name").' is ready for use',"created");
		}
		$adminGroup = EcommerceRole::get_admin_group();
		$adminCode = EcommerceConfig::get("EcommerceRole", "admin_group_code");
		$adminName = EcommerceConfig::get("EcommerceRole", "admin_group_name");
		$adminPermissionCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
		$adminRoleTitle = EcommerceConfig::get("EcommerceRole", "admin_role_title");
		if(!$adminGroup) {
			$adminGroup = new Group();
			$adminGroup->Code = $adminCode;
			$adminGroup->Title = $adminName;
			$adminGroup->write();
			Permission::grant( $adminGroup->ID, $adminPermissionCode);
			DB::alteration_message($adminName.' Group created',"created");
		}
		elseif(DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '".$adminGroup->ID."' AND \"Code\" LIKE '".$adminPermissionCode."'")->numRecords() == 0 ) {
			Permission::grant($adminGroup->ID, $adminPermissionCode);
			DB::alteration_message($adminName.' permissions granted',"created");
		}
		else {
			DB::alteration_message($adminName." permissions already granted","created");
		}
		$permissionRole = PermissionRole::get()
			->Filter(array("Title" => $adminRoleTitle))
			->First();
		if($permissionRole) {
			//do nothing
			DB::alteration_message($adminName.' role in place',"created");
		}
		else {
			$permissionRole = new PermissionRole();
			$permissionRole->Title = $adminRoleTitle;
			$permissionRole->OnlyAdminCanApply = true;
			$permissionRole->write();
			DB::alteration_message($adminName.' role created',"created");
		}
		if($permissionRole) {
			$permissionArray = EcommerceConfig::get("EcommerceRole", "admin_role_permission_codes");
			if(is_array($permissionArray) && count($permissionArray) && $permissionRole) {
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
			if($adminGroup) {
				$existingGroups = $permissionRole->Groups();
				$existingGroups->add($adminGroup);
			}
		}
	}

}
