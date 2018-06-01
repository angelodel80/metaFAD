<?php

class metafad_workflow_processes_controllers_Detail extends org_glizycms_contents_controllers_moduleEdit_Edit
{
     public function execute($id)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'));
            $data['processId'] = $id;
            $this->view->setData($data);
        }
    }
}