<?php
class metafad_teca_MAG_controllers_ajax_CreateMediaFromList extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($stru,$key,$id)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }
        
      $it = org_glizy_objectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model')
              ->where('document_id',$stru)->first();
      $logicalStru = json_decode($it->logicalSTRU);
      $physicalStru = json_decode($it->physicalSTRU);

      //Estraggo informazioni su docstru
      $docStruProxy = $this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
      $rootId = $docStruProxy->getRootNodeByDocumentId($id);

      $idList = array();
      $struToImport = array();

      //Cerco i media da collegare in base al confronto di $e->keyNode con $idList
      foreach ($physicalStru as $elements) {
        //Ogni elements è un array con un singolo tipo di media
        $count = 1;
        foreach ($elements as $e) {
          if(in_array($e->id,$key))
          {
            //TODO leggere $e->metadata per relativi metadati precompilati da DAM
            $docStruProxy->saveNewMedia($e,$rootId->docstru_id,$count);
            $count++;
          }
        }
      }

      return array('sendOutput' => 'fileTabs', 'sendOutputState' => 'edit', 'sendOutputFormat' => 'html');
    }
}
