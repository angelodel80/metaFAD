<?php
class metafad_usersAndPermissions_roles_controllers_Delete extends metafad_common_controllers_Command
{
    function execute($model, $id)
    {   
        $this->checkPermissionForBackend('delete');
        
        if ($id) {
			$proxy = org_glizy_objectFactory::createObject('metafad.usersAndPermissions.roles.models.proxy.RolesContentProxy');
            $proxy->delete($id, $model);
			org_glizy_helpers_Navigation::goHere();
        }
    }
}