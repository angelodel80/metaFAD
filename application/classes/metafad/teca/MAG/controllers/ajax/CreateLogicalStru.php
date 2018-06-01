<?php
class metafad_teca_MAG_controllers_ajax_CreateLogicalStru extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($type,$stru,$key,$id,$option)
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

      //Estraggo tutti gli id dei nodi che voglio salvare
      //Se $key è settato devo escludere tutti i nodi che non ne sono figli
      $idList = array();
      $idCompleteList = array();
      $this->getElementsId($logicalStru,$idCompleteList);
      $struToImport = array();
      if($key)
      {
        foreach ($key as $v) {
          $struToImport[] = $this->exploreLogicalStruWithKey($logicalStru,$v);
        }
        $this->getElementsId($struToImport,$idList);
      }
      else
      {
        $this->getElementsId($logicalStru,$idList);
      }

      //Import STRU logica
      //Conto le immagini e le varie sequence start e stop number
      //Escludo dalla conta il nodo exclude
      $imageArray = $this->getImagesFromStrumag($idCompleteList,$physicalStru);

      $imageArrayFinal = array();
      foreach($idList as $id) {
        $imageArrayFinal[$id] = $imageArray[$id];
      }
      $imageArray = $imageArrayFinal;

      //Estraggo la struttura e creo la vera STRU
      $struArray = array();
      $this->buildStru($logicalStru,$struArray,0);
      $finalStru = $this->createSTRU($imageArray,$struArray);

      //Se sto filtrando su alcuni nodi selezionati
      if(!empty($idList))
      {
        $struAppoggio = array();
        foreach ($idList as $key) {
          $struAppoggio[$key] = $finalStru[$key];
        }
        $finalStru = $struAppoggio;
      }

      return array('logicalStru' => json_encode($finalStru));
    }

    public function exploreLogicalStruWithKey($stru,$key)
    {
      foreach ($stru as $k => $value) {
        if($value->key == $key)
        {
          return $value;
        }
        else if($value->children)
        {
          $v = $this->exploreLogicalStruWithKey($value->children,$key);
        }
      }
      return $v;
    }

    public function getElementsId($stru,&$idList)
    {
      foreach ($stru as $k => $value) {
        if($value->key == 'exclude')
        {
          continue;
        }
        $idList[] = $value->key;
        if($value->children)
        {
          $this->getElementsId($value->children,$idList);
        }
      }
    }

    public function getImagesFromStrumag($idList,$physicalStru)
    {
      //Conteggio start-stop sequence
      $countIdArray = array();
      foreach ($idList as $k) {
        $countIdArray[$k] = 0;
      }
      foreach ($physicalStru->image as $img) {
        if(in_array($img->keyNode,$idList))
        {
          $countIdArray[$img->keyNode]++;
        }
      }

      $imageArray = array();
      $countSequence = 1;
      foreach ($countIdArray as $key => $value) {
        if(!array_key_exists($key,$imageArray) && $value != 0)
        {
          $imageArray[$key]['start'] = $countSequence;
          $imageArray[$key]['stop'] = $countSequence + $value - 1;
          $countSequence += $value;
        }
      }
      return $imageArray;
    }

    public function buildStru($stru,&$struArray,$parent)
    {
      $countSequence = 1;
      foreach ($stru as $l) {
        if($l->key == 'exclude')
        {
          continue;
        }
        $struArray[$l->key]['sequence_number'] = $countSequence;
        $struArray[$l->key]['parent'] = $parent;
        $struArray[$l->key]['title'] = $l->title;
        if($l->children)
        {
          $this->buildStru($l->children,$struArray,$l->key);
        }
        $countSequence++;
      }
    }

    public function createSTRU($imageArray,$struArray)
    {
      $stru = array();

      foreach ($struArray as $key => $value) {
        $stru[$key]['sequence_number'] = $value['sequence_number'];
        $stru[$key]['parent'] = $value['parent'];
        $stru[$key]['title'] = $value['title'];
        $stru[$key]['start'] = $imageArray[$key]['start'];
        $stru[$key]['stop'] = $imageArray[$key]['stop'];
      }

      return $stru;
    }
}
