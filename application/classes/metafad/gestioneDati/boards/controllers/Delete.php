<?php
class metafad_gestioneDati_boards_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id, $model)
    {
        $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $data = $contentProxy->loadContent($id, $model);
        if (!$data['instituteKey']) {
            $data = $contentProxy->loadContent($id, $model, 'DRAFT');
        }
        $this->checkPermissionAndInstitute('delete', $data['instituteKey']);

        $iccdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy');
        $iccdProxy->delete($id, $model);
        org_glizy_helpers_Navigation::goHere();
    }
}