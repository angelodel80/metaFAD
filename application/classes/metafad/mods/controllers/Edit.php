<?php
class metafad_mods_controllers_Edit extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $modsService = __ObjectFactory::createObject('metafad.mods.services.ModsService');
        $c = $this->view->getComponentById('__model');
        __Request::set('model', $c->getAttribute('value'));
        
        $data = $modsService->load($id, $c->getAttribute('value'), $this->user);

        if ($id) {
            $this->checkPermissionAndInstitute('edit', $data['instituteKey']);
        }
        
        $this->view->setData($data);
    }
}