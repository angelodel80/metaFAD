<?php
class metafad_common_controllers_ajax_CommandAjax extends org_glizy_mvc_core_CommandAjax
{
    /**
     * Check the user permission
     * @param  string $service
     * @param  string $action [description]
     */
    protected function checkPermission($service=null, $action=null)
    {
        if (!$this->user->isLogged()) {
            $this->directOutput = true;
            return array('url' => __Routing::makeUrl('login'));
        }

        if ($service && $action) {
            $canAccess = $this->user->acl($service, $action, false);
        }

        if (!$canAccess) {
            $this->directOutput = true;
            return array('errors' => array('Permessi non sufficienti per eseguire l\'operazione richiesta'));
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
            $this->directOutput = true;
            return array('url' => __Routing::makeUrl('login'));
        }

        return $this->checkPermission(__Request::get('pageId'), $action);
    }

    protected function checkInstitute($obj, $status='PUBLISHED')
    {
        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $data = $contentProxy->loadContent($obj->__id, $obj->__model, $status);

        if (!$data['instituteKey']) {
            $tryStatus = $status == 'DRAFT' ? 'PUBLISHED' : 'DRAFT';
            $data = $contentProxy->loadContent($obj->__id, $obj->__model, $tryStatus);
        }

        if ($instituteKey != '*' && $data['instituteKey'] != $instituteKey) {
            $this->directOutput = true;
            return array('errors' => array('Permessi non sufficienti per eseguire l\'operazione richiesta'));
        }

        return true;
    }

    public function checkPermissionAndInstitute($action, $data, $status='PUBLISHED')
    {
        $result = $this->checkPermissionForBackend($action);
        if (is_array($result)) {
            return $result;
        }

        $objData = glz_maybeJsonDecode($data, false);

        if ($objData->__id) {
            $result = $this->checkInstitute($objData, $status);
            if (is_array($result)) {
                return $result;
            }
        }

        return $result;
    }
}
