<?php
class metafad_common_controllers_Command extends org_glizy_mvc_core_Command
{
    /**
     * Check the user permission
     * @param  string $service
     * @param  string $action [description]
     */
    protected function checkPermission($service=null, $action=null)
    {
        if (!$this->user->isLogged()) {
            org_glizy_helpers_Navigation::accessDenied();
        }

        if ($service && $action) {
            $canAccess = $this->user->acl($service, $action, false);
        }

        if (!$canAccess) {
            org_glizy_application_MessageStack::add('Permessi non sufficienti per eseguire l\'operazione richiesta', GLZ_MESSAGE_ERROR);
            org_glizy_helpers_Navigation::goHere();
        }

        return true;
    }

    /**
     * Check the user permission
     * @param  string $service
     * @param  string $action [description]
     */
    protected function checkPermissionForBackend($action=null)
    {
        if (!$this->user->backEndAccess) {
            org_glizy_helpers_Navigation::accessDenied();
        }

        return $this->checkPermission(__Request::get('pageId'), $action);
    }

    protected function checkInstitute($instituteKeyToCheck)
    {
        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        if ($instituteKey != '*' && $instituteKeyToCheck != $instituteKey) {
            org_glizy_application_MessageStack::add('Permessi non sufficienti per eseguire l\'operazione richiesta', GLZ_MESSAGE_ERROR);
            org_glizy_helpers_Navigation::goHere();
        }
    }

    protected function checkPermissionAndInstitute($action, $instituteKey)
    {
        $this->checkPermissionForBackend($action);
        $this->checkInstitute($instituteKey);
    }
}