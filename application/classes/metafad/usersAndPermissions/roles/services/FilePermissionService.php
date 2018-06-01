<?php
class metafad_usersAndPermissions_roles_services_FilePermissionService extends metafad_usersAndPermissions_roles_services_AbstractPermissionService
{
    protected $permissions;

    public function __construct($filename)
    {
        $filename = __Paths::getRealPath('APPLICATION').'config/'.$filename;
        $xml = __ObjectFactory::createObject('org.glizy.parser.XML');
        $xml->loadAndParseNS($filename);
        $permissions = $xml->getElementsByTagName('permission');

        $this->permissions = array();

        foreach ($permissions as $permission) {
            $this->permissions[] = $permission;
        }
    }

    protected function fetch()
    {
        $permission = $this->permissions[$this->pos];

        if (!$permission) {
            $this->data = null;
            return;
        }

        $permissionVO = __ObjectFactory::createObject('metafad.usersAndPermissions.roles.models.vo.PermissionVO',
            $permission->getAttribute('id'),
            $permission->getAttribute('label'),
            $permission->getAttribute('acl'),
            $permission->getAttribute('aclPageTypes')
        );

        $this->data = $permissionVO;
        $this->pos++;
    }
}