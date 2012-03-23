<?php

class CreateEcommerceMemberGroups extends BuildTask{

	protected $title = "Create E-commerce Member Groups";

	protected $description = "Create the member groups and members for e-commerce.";

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
		if(!$customerGroup = EcommerceRole::get_customer_group()) {
			die("ERROR");
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
		$permissionRole = DataObject::get_one("PermissionRole", "\"Title\" = '".$adminRoleTitle."'");
		if(!$permissionRole) {
			$permissionRole = new PermissionRole();
			$permissionRole->Title = $adminRoleTitle;
			$permissionRole->OnlyAdminCanApply = true;
			$permissionRole->write();
		}
		if($permissionRole) {
			$permissionArray = EcommerceConfig::get("EcommerceRole", "admin_role_permission_codes");
			if(is_array($permissionArray) && count($permissionArray) && $permissionRole) {
				foreach($permissionArray as $permissionCode) {
					$permissionRoleCode = DataObject::get_one("PermissionRoleCode", "\"Code\" = '$permissionCode'");
					if(!$permissionRoleCode) {
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
