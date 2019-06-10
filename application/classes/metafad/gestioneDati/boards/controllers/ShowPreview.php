<?php
class metafad_gestioneDati_boards_controllers_ShowPreview extends metafad_common_controllers_Command
{
    public function execute($id, $templateID)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            __Request::set('model', $c->getAttribute('value'));

            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'));
            
            $data['__id'] = $id;
            $this->view->setData($data);
            if (!$data['isTemplate'] || $data['isTemplate'] != 1) {
                $this->setComponentsVisibility('templateTitle', false);
            }
        }
    }
}
