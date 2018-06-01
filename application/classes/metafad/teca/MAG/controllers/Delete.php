<?php

class metafad_teca_MAG_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id, $model, $reindex = true)
    {
        __Session::remove('idLinkedImages');
        if ($id) {
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $model);

            $this->checkPermissionAndInstitute('delete', $data['instituteKey']);

            $magProxy = __ObjectFactory::createObject('metafad.teca.MAG.models.proxy.MagProxy');
            $magProxy->delete($id, $model, false);

            org_glizy_helpers_Navigation::goHere();
        }
    }

}
