<?php


/**
 * create the e-commerce specific Member Groups.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskCreateMemberGroups extends BuildTask
{
    protected $title = 'Create e-commerce Member Groups';

    protected $description = 'Create the member groups and members for e-commerce, such as Customers and Shop Admininistrators.';

    public function run($request)
    {
        db::alteration_message('========================== <br />creating customer group', 'created');
        $this->CreateGroup(
            $code = EcommerceConfig::get('EcommerceRole', 'customer_group_code'),
            $name = EcommerceConfig::get('EcommerceRole', 'customer_group_name'),
            $parentGroup = null,
            $permissionCode = EcommerceConfig::get('EcommerceRole', 'customer_permission_code'),
            $roleTitle = '',
            $permissionArray = array(),
            $member = null
        );

        db::alteration_message('========================== <br />creating sales manager', 'created');
        //work out email
        $email = EcommerceConfig::get('EcommerceRole', 'admin_group_user_email');
        if (!$email) {
            $email = 'websales@'.$_SERVER['HTTP_HOST'];
        }
        //create member
        $filter = array('Email' => $email);
        $member = Member::get()->filter($filter)->first();
        if (!$member) {
            $member = Member::create($filter);
        }
        $firstName = EcommerceConfig::get('EcommerceRole', 'admin_group_user_first_name');
        if (!$firstName) {
            $firstName = 'Web';
        }
        $surname = EcommerceConfig::get('EcommerceRole', 'admin_group_user_surname');
        if (!$surname) {
            $surname = 'Sales';
        }

        $member->FirstName = $firstName;
        $member->Surname = $surname;
        $member->write();
        db::alteration_message('================================<br />creating shop admin group ', 'created');

        $this->CreateGroup(
            $code = EcommerceConfig::get('EcommerceRole', 'admin_group_code'),
            $name = EcommerceConfig::get('EcommerceRole', 'admin_group_name'),
            $parentGroup = null,
            $permissionCode = EcommerceConfig::get('EcommerceRole', 'admin_permission_code'),
            $roleTitle = EcommerceConfig::get('EcommerceRole', 'admin_role_title'),
            $permissionArray = EcommerceConfig::get('EcommerceRole', 'admin_role_permission_codes'),
            $member
        );
    }

    /**
     * set up a group with permissions, roles, etc...
     * also @see EcommerceRole::providePermissions
     * also note that this class implements PermissionProvider.
     *
     * @param string          $code            code for the group - will always be converted to lowercase
     * @param string          $name            title for the group
     * @param Group | String  $parentGroup     group object that is the parent of the group. You can also provide a string (name / title of group)
     * @param string          $permissionCode  Permission Code for the group (e.g. CMS_DO_THIS_OR_THAT)
     * @param string          $roleTitle       Role Title - e.g. Store Manager
     * @param array           $permissionArray Permission Array - list of permission codes applied to the group
     * @param Member | String $member          Default Member added to the group (e.g. sales@mysite.co.nz). You can also provide an email address
     */
    public function CreateGroup($code, $name, $parentGroup = null, $permissionCode = '', $roleTitle = '', $permissionArray = array(), $member = null)
    {
        //changing to lower case seems to be very important
        //unidentified bug so far
        $code = strtolower($code);
        if (!$code) {
            user_error("Can't create a group without a $code ($name)");
        }
        if (!$name) {
            user_error("Can't create a group without a $name ($code)");
        }
        $group = Group::get()->filter(array('Code' => $code))->first();
        $groupCount = Group::get()->filter(array('Code' => $code))->count();
        $groupStyle = 'updated';
        if ($groupCount > 1) {
            user_error("There is more than one group with the $name ($code) Code");
        }
        if (!$group) {
            $group = Group::create();
            $group->Code = $code;
            $groupStyle = 'created';
        }
        $group->Locked = 1;
        $group->Title = $name;
        $parentGroupStyle = 'updated';
        if ($parentGroup) {
            DB::alteration_message('adding parent group');
            if (is_string($parentGroup)) {
                $parentGroupName = $parentGroup;
                $parentGroup = Group::get()->filter(array('Title' => $parentGroupName))->first();
                if (!$parentGroup) {
                    $parentGroup = Group::create();
                    $parentGroupStyle = 'created';
                    $parentGroup->Title = $parentGroupName;
                    $parentGroup->write();
                    DB::alteration_message("$parentGroupStyle $parentGroupName", $parentGroupStyle);
                }
            }
            if ($parentGroup) {
                $group->ParentID = $parentGroup->ID;
            }
        }
        $group->write();
        DB::alteration_message("$groupStyle $name ($code) group", $groupStyle);
        $doubleGroups = Group::get()
            ->filter(array('Code' => $code))
            ->exclude(array('ID' => $group->ID));
        if ($doubleGroups->count()) {
            DB::alteration_message($doubleGroups->count().' groups with the same name', 'deleted');
            $realMembers = $group->Members();
            foreach ($doubleGroups as $doubleGroup) {
                $fakeMembers = $doubleGroup->Members();
                foreach ($fakeMembers as $fakeMember) {
                    DB::alteration_message('adding customers: '.$fakeMember->Email, 'created');
                    $realMembers->add($fakeMember);
                }
                DB::alteration_message('deleting double group ', 'deleted');
                $doubleGroup->delete();
            }
        }
        if ($permissionCode) {
            $permissionCodeCount = DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '".$group->ID."' AND \"Code\" LIKE '".$permissionCode."'")->numRecords();
            if ($permissionCodeCount == 0) {
                DB::alteration_message('granting '.$name." permission code $permissionCode ", 'created');
                Permission::grant($group->ID, $permissionCode);
            } else {
                DB::alteration_message($name." permission code $permissionCode already granted");
            }
        }
        //we unset it here to avoid confusion with the
        //other codes we use later on
        if($permissionArray) {
            if( is_string($permissionArray)) {
                $permissionArray = array($permissionArray);
            }
            $permissionArray[] = $permissionCode;
        }
        unset($permissionCode);
        if ($roleTitle) {
            $permissionRole = PermissionRole::get()
                ->Filter(array('Title' => $roleTitle))
                ->First();
            $permissionRoleCount = PermissionRole::get()
                ->Filter(array('Title' => $roleTitle))
                ->Count();
            if ($permissionRoleCount > 1) {
                db::alteration_message("There is more than one Permission Role with title $roleTitle ($permissionCodeObjectCount)", 'deleted');
                $permissionRolesToDelete = PermissionRole::get()
                    ->Filter(array('Title' => $roleTitle))
                    ->Exclude(array('ID' => $permissionRole->ID));
                foreach ($permissionRolesToDelete as $permissionRoleToDelete) {
                    db::alternation_message("DELETING double permission role $roleTitle", 'deleted');
                    $permissionRoleToDelete->delete();
                }
            }
            if ($permissionRole) {
                //do nothing
                DB::alteration_message("$roleTitle role in place");
            } else {
                DB::alteration_message("adding $roleTitle role", 'created');
                $permissionRole = PermissionRole::create();
                $permissionRole->Title = $roleTitle;
                $permissionRole->OnlyAdminCanApply = true;
                $permissionRole->write();
            }
            if ($permissionRole) {
                if (is_array($permissionArray) && count($permissionArray)) {
                    DB::alteration_message('working with '.implode(', ', $permissionArray));
                    foreach ($permissionArray as $permissionRoleCode) {
                        $permissionRoleCodeObject = PermissionRoleCode::get()
                            ->Filter(array('Code' => $permissionRoleCode, 'RoleID' => $permissionRole->ID))
                            ->First();
                        $permissionRoleCodeObjectCount = PermissionRoleCode::get()
                            ->Filter(array('Code' => $permissionRoleCode, 'RoleID' => $permissionRole->ID))
                            ->Count();
                        if ($permissionRoleCodeObjectCount > 1) {
                            $permissionRoleCodeObjectsToDelete = PermissionRoleCode::get()
                                ->Filter(array('Code' => $permissionRoleCode, 'RoleID' => $permissionRole->ID))
                                ->Exclude(array('ID' => $permissionRoleCodeObject->ID));
                            foreach ($permissionRoleCodeObjectsToDelete as $permissionRoleCodeObjectToDelete) {
                                db::alteration_message("DELETING double permission code $permissionRoleCode for ".$permissionRole->Title, 'deleted');
                                $permissionRoleCodeObjectToDelete->delete();
                            }
                            db::alteration_message('There is more than one Permission Role Code in '.$permissionRole->Title." with Code = $permissionRoleCode ($permissionRoleCodeObjectCount)", 'deleted');
                        }
                        if ($permissionRoleCodeObject) {
                            //do nothing
                        } else {
                            $permissionRoleCodeObject = PermissionRoleCode::create();
                            $permissionRoleCodeObject->Code = $permissionRoleCode;
                            $permissionRoleCodeObject->RoleID = $permissionRole->ID;
                        }
                        DB::alteration_message('adding '.$permissionRoleCodeObject->Code.' to '.$permissionRole->Title);
                        $permissionRoleCodeObject->write();
                    }
                }
                if ($group && $permissionRole) {
                    if (DB::query('SELECT COUNT(*) FROM Group_Roles WHERE GroupID = '.$group->ID.' AND PermissionRoleID = '.$permissionRole->ID)->value() == 0) {
                        db::alteration_message('ADDING '.$permissionRole->Title.' permission role  to '.$group->Title.' group', 'created');
                        $existingGroups = $permissionRole->Groups();
                        $existingGroups->add($group);
                    } else {
                        db::alteration_message('CHECKED '.$permissionRole->Title.' permission role  to '.$group->Title.' group');
                    }
                } else {
                    db::alteration_message('ERROR: missing group or permissionRole', 'deleted');
                }
            }
        }
        if ($member) {
            if (is_string($member)) {
                $email = $member;
                $member = Member::get()->filter(array('Email' => $email))->first();
                if (!$member) {
                    DB::alteration_message('Creating default user', 'created');
                    $member = Member::create();
                    $member->FirstName = $code;
                    $member->Surname = $name;
                    $member->Email = $email;
                    $member->write();
                }
            }
            if ($member) {
                DB::alteration_message(' adding member '.$member->Email.' to group '.$group->Title, 'created');
                $member->Groups()->add($group);
            }
        } else {
            DB::alteration_message('No need to add user');
        }
    }
}
