<?php
class metafad_gestioneDati_boards_controllers_NormalizeMassive extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('edit');

      $ids = explode("-",$id);
      $model = $this->view->getComponentById('__model')->getAttribute('value');
      foreach ($ids as $id) {
        //Prendo il record in questione
        $ar = org_glizy_objectFactory::createModel($model);
        $ar->load($id);
        
        $this->checkInstitute($ar->instituteKey);

        //Estraggo i dati inerenti la versione di scheda semplificata
        $modelSimpleId = $ar->simpleForm;
        $sAr = org_glizy_objectFactory::createModel('metafad.gestioneDati.schedeSemplificate.models.Model');
        $sAr->load($modelSimpleId);

        //Creo un model per fare il trasferimento
        $originalModel = $sAr->form->id;
        $oAr = org_glizy_objectFactory::createModel($originalModel.'.models.Model');

        //Estraggo i valori dalla semplificata e li aggiungo alla completa
        $values = $ar->getValuesAsArray(false,true,false,false);
        foreach ($values as $key => $value) {
          if($key != 'simpleForm' || $key != 'document_detail_note' )
          {
            $oAr->$key = $value;
          }
        }
        //Salvataggio su db
        $newId = $oAr->save(null,false,'DRAFT');
        //preparo i dati per salvataggio SOLR
        $data = $oAr->getRawData();
        $data->__id = $newId;
        $data->__model = $originalModel.'.models.Model';


        $cl = new stdClass();
        $it = org_glizy_ObjectFactory::createModelIterator($originalModel.'.models.Model');

        if ($it->getArType() === 'document') {
            $it->setOptions(array('type' => 'PUBLISHED_DRAFT'));
        }

        $it->where('document_id',$newId, 'ILIKE');
        foreach ($it as $record) {
            $cl->className = $record->getClassName(false);
            $cl->isVisible = $record->isVisible();
            $cl->isTranslated = $record->isTranslated();
            $cl->hasPublishedVersion = $record->hasPublishedVersion();
            $cl->hasDraftVersion = $record->hasDraftVersion();
            $cl->document_detail_status = $record->getStatus();
        }

        $data->document = json_encode($cl);

        //Salvo su SOLR
        $data->__commit = true;
        $evt = array('type' => 'insertRecord', 'data' => array('data' => $data, 'option' => array('commit' => true)));
        $this->dispatchEvent($evt);

        //Cancello da DB e SOLR i dati della semplificata
        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);
        $ar->delete();
      }
      //Redirect a index
      org_glizy_helpers_Navigation::goHere();
    }
}
