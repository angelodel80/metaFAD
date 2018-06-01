<?php
class metafad_usersAndPermissions_institutes_controllers_SelectInstitute extends metafad_common_controllers_Command
{
    public function execute($instituteKey)
    {
        $this->checkPermissionForBackend('visible');
        
        metafad_usersAndPermissions_Common::setInstituteKey($instituteKey);
        $this->changePage('linkHome');
    }
}