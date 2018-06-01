<?php
class metafad_usersAndPermissions_users_controllers_ajax_SaveClosePersonal extends metafad_usersAndPermissions_users_controllers_ajax_Save
{
    public function execute($data)
    {
        $result = parent::execute($data);
        
        if ($result['errors']) {
            return $result;
        }
        
        return array('url' => __Session::get('lastPage'));
    }
}
