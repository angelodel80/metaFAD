<?php
class metafad_teca_STRUMAG_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id, $model)
    {
        $this->checkPermissionForBackend('delete');
        
        if ($id) {
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $model);

            $this->checkPermissionAndInstitute('delete', $data['instituteKey']);

            $struMagProxy = __ObjectFactory::createObject('metafad.teca.STRUMAG.models.proxy.StruMagProxy');
            $struMagProxy->delete($id);

            org_glizy_helpers_Navigation::goHere();
        }
    }
}
