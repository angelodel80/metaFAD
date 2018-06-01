<?php

class metafad_teca_mets_controllers_media_Edit extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        if ($id) {
            // read the module content
            $c = $this->view->getComponentById('__model');
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
            $data = $contentproxy->loadContent($id, $c->getAttribute('value'));

            $this->checkPermissionAndInstitute('edit', $data['instituteKey']);

            //Inizializzo il componente per il recupero dei group id
            // $groups = array('img','audio','video');
            // foreach ($groups as $group) {
            //   $groupId = $this->view->getComponentById($group.'groupID');
            //   if($groupId){
            //     $groupId->setAttribute('data','type=selectfrom;multiple=false;add_new_values=false;proxy=metafad.teca.mets.models.proxy.GroupProxy;proxy_params={##type##:##'.$group.'##,##id##:##'.$id.'##};');
            //   }
            // }

//  TODO verifica se il record esiste
            $data['__id'] = $id;
            $this->view->setData($data);
        }
    }
}
