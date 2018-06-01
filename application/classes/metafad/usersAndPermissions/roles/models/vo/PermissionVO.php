<?php
class metafad_usersAndPermissions_roles_models_vo_PermissionVO
{
    public $id;
    public $label;
    public $acl;
    public $aclPageTypes;

    public function __construct($id, $label, $acl, $aclPageTypes)
    {
        $this->id = $id;
        $this->label = $label;
        $this->acl = $acl;
        $this->aclPageTypes = $aclPageTypes;
    }
}