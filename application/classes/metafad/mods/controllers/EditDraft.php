<?php
class metafad_mods_controllers_EditDraft extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $modsService = __ObjectFactory::createObject('metafad.mods.services.ModsService');
        $c = $this->view->getComponentById('__model');
        $data = $modsService->load($id, $c->getAttribute('value'), $this->user, 'DRAFT');
        
        if ($id) {
            $this->checkPermissionAndInstitute('edit', $data['instituteKey']);
        }
        
        $this->view->setData($data);
    }
}