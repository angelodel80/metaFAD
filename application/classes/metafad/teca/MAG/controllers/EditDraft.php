<?php
class metafad_teca_MAG_controllers_EditDraft extends metafad_common_controllers_Command
{
    public function execute($id, $templateID)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            __Session::set('idLinkedImages',$id);
            __Session::set('prevState','linkEditDraft');
            __Session::set('relationType','Fa parte di');
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'), 'DRAFT');
            $data['__id'] = $id;
            if ($data['linkedForm']) {
                $this->view->getComponentById('linkedFormContainer')->setAttribute('cssClass', 'linkedFormContainer col-sm-5');
            } else {
                $this->view->getComponentById('linkedFormContainer')->setAttribute('cssClass', 'linkedFormContainer col-sm-5 hide');
            }
            $this->checkPermissionAndInstitute('editDraft', $data['instituteKey'], 'DRAFT');

            if(!$data['isTemplate'] || $data['isTemplate'] != 1){
                $this->setComponentsVisibility('templateTitle', false);
            }

            $data['GEN_creation'] = $data['document_creationDate'];
            $data['GEN_lastUpdate'] = $data['document_detail_modificationDate'];
            $this->view->getComponentById('struComponent')->setAttribute('data',json_encode($data['linkedStru']));
            $this->view->getComponentById('struComponent')->setAttribute('stru',json_encode($data['logicalStru']));

            $this->view->setData($data);
        }
        else {
            $this->view->getComponentById('showImagesLink')->setAttribute('visible', false);
            $this->view->getComponentById('linkedFormContainer')->setAttribute('cssClass', 'linkedFormContainer col-sm-5 hide');
            if ($templateID != '0' && $templateID != '') {
                $c = $this->view->getComponentById('__model');
                $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
                $data = $contentProxy->loadContent($templateID, $c->getAttribute('value'));

                $this->checkPermissionAndInstitute('edit', $data['instituteKey']);

                unset($data['isTemplate']);
                unset($data['templateTitle']);
                $this->view->setData($data);
                $this->setComponentsVisibility('templateTitle', false);
            } else if($templateID == '0') {
                $data['isTemplate'] = 1;
                $this->view->setData($data);
                $this->setComponentsVisibility('templateTitle', true);
            } else {
                $this->setComponentsVisibility('templateTitle', false);
            }
        }


    }
}
