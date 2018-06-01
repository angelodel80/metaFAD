<?php

class metafad_teca_MAG_controllers_media_Edit extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('edit');
        
        if ($id) {
            // read the module content
            $c = $this->view->getComponentById('__model');
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $c->getAttribute('value'));

            //Inizializzo il componente per il recupero dei group id
            $groups = array('img','audio','video');
            foreach ($groups as $group) {
              $groupId = $this->view->getComponentById($group.'groupID');
              if($groupId){
                $groupId->setAttribute('data','type=selectfrom;multiple=false;add_new_values=false;proxy=metafad.teca.MAG.models.proxy.GroupProxy;proxy_params={##type##:##'.$group.'##,##id##:##'.$id.'##};');
              }
            }

//  TODO verifica se il record esiste
            $data['__id'] = $id;
            $this->view->setData($data);
        }
    }
}
