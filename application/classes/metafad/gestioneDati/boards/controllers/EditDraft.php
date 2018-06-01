<?php
class metafad_gestioneDati_boards_controllers_EditDraft extends metafad_common_controllers_Command
{
    public function execute($id, $templateID)
    {
        __Session::set('prevState','linkEditDraft');

        //Controllo campi per schede semplificate
        $simpleForm = $this->view->getComponentById('simpleForm');
        if($simpleForm)
        {
          $simpleAdminHelper = org_glizy_objectFactory::createObject('metafad.gestioneDati.schedeSemplificate.views.helpers.SimpleAdminHelper');
          $fields = $simpleAdminHelper->getFields($simpleForm->getAttribute('value'));
          foreach ($fields['all'] as $f) {
            if($this->view->getComponentById($f.'-tab'))
            {
              $this->view->getComponentById($f.'-tab')->setAttribute('visible',false);
            }
            $this->view->getComponentById($f)->setAttribute('visible',false);
            $this->view->getComponentById($f)->setAttribute('required',false);
          }
          foreach ($fields['toShow'] as $k => $f) {
            if($f['tab'])
            {
              $this->view->getComponentById($k.'-tab')->setAttribute('visible',true);
              $this->view->getComponentById($k)->setAttribute('visible',true);
            }
            if($f['visible'])
            {
              $this->view->getComponentById($k)->setAttribute('visible',true);
            }
            if($f['mandatory'])
            {
              $this->view->getComponentById($k)->setAttribute('required',true);
            }
          }
        }

        if ($id) {
            $c = $this->view->getComponentById('__model');

            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'), 'DRAFT');

            $this->checkPermissionAndInstitute('editDraft', $data['instituteKey']);

            $linkToSbn = $this->view->getComponentById('linkToSbn');
            if($data['BID'] && $linkToSbn)
            {
              $linkToSbn->setAttribute('type','BID');
              $linkToSbn->setAttribute('recordId',$data['BID']);
            }
            else if($data['VID'] && $linkToSbn)
            {
              $linkToSbn->setAttribute('type','VID');
              $linkToSbn->setAttribute('recordId',$data['VID']);
            }
            else if($linkToSbn) {
              $linkToSbn->setAttribute('enabled',false);
            }

            if($data['BIB'][0]->__BIB)
            {
              $data['BIB'] = org_glizy_objectFactory::createObject('metafad.gestioneDati.boards.views.helpers.BibHelper')
                             ->getBibValues($data['BIB']);
            }
            if($data['AUT'][0]->__AUT)
            {
              $data['AUT'] = org_glizy_objectFactory::createObject('metafad.gestioneDati.boards.views.helpers.AutHelper')
                             ->getAutValues($data['AUT']);
            }

            if($data['relatedStru']){
                $decodeStru = json_decode($data['relatedStru']);
                if($document->load($decodeStru->id)){
                    if($document->getRawData()->physicalSTRU){
                        $data['physicalSTRU'] = $document->physicalSTRU;
                    }
                    if($document->getRawData()->logicalSTRU){
                        $data['logicalSTRU'] = $document->logicalSTRU;
                    }
                }
            }

            $data['__id'] = $id;
            $this->view->setData($data);
            if(!$data['isTemplate'] || $data['isTemplate'] != 1){
                $this->setComponentsVisibility('templateTitle', false);
            }
        }
        else {
            $this->setComponentsVisibility('historyTab', false);
            $this->setComponentsVisibility('relationsTab', false);
            $this->setComponentsVisibility('linkShowImages', false);
            $this->view->getComponentById('linkedImages')->setAttribute('enabled',false);

            if ($templateID != '0' && $templateID != '') {
                $c = $this->view->getComponentById('__model');
                $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
                $data = $contentProxy->loadContent($templateID, $c->getAttribute('value'));

                $this->checkPermissionAndInstitute('editDraft', $data['instituteKey']);

                unset($data['isTemplate']);
                unset($data['templateTitle']);
                $this->view->setData($data);
                $this->setComponentsVisibility('templateTitle', false);
            } else if ($templateID == '0') {
                $data['isTemplate'] = 1;
                $this->view->setData($data);
                $this->setComponentsVisibility('templateTitle', true);
            } else {
                $this->setComponentsVisibility('templateTitle', false);
            }
        }
    }
}
