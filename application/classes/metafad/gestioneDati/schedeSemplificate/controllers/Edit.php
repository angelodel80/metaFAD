<?php
class metafad_gestioneDati_schedeSemplificate_controllers_Edit extends org_glizycms_contents_controllers_moduleEdit_Edit
{
   public function execute($id)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $c->getAttribute('value'));
            $data['__id'] = $id;
            $fieldList = $this->view->getComponentById('fieldList');
            $this->view->getComponentById('name')->setAttribute('readOnly',true);
            $this->view->getComponentById('form')->setAttribute('readOnly', true);
            $fieldList->setAttribute('moduleName',$data['form']->id);
            $fieldList->setAttribute('fieldJson',$data['fieldJson']);
            $this->view->setData($data);
        }
    }
}
