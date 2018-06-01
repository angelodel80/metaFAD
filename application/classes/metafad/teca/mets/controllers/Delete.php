<?php

class metafad_teca_mets_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id, $model, $reindex = true)
    {
        __Session::remove('idLinkedImages');
        if ($id) {
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $model);

            $this->checkPermissionAndInstitute('delete', $data['instituteKey']);

            $magProxy = __ObjectFactory::createObject('metafad.teca.mets.models.proxy.MetsProxy');
            $magProxy->delete($id, $model);

            org_glizy_helpers_Navigation::goHere();
        }
    }

}
