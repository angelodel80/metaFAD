<?php

class metafad_teca_MAG_models_proxy_MagRelationProxy extends GlizyObject
{
  public function findTerm($fieldName, $model, $query, $term, $proxyParams)
  {
    $type = (__Session::get('relationType')) ? __Session::get('relationType') : 'Fa parte di';
    $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Model')
          ->setOptions(array('type' => 'PUBLISHED_DRAFT'));
    $result = array();
    foreach ($it as $ar) {
      if($term != '')
      {
        if (stripos($ar->BIB_dc_title[0]->BIB_dc_title_value." {".$ar->BIB_dc_identifier_index."}",$term) === false)
        {
          continue;
        }
      }
      $result[] = array(
          'id' => $ar->document_id,
          'text' => "'".$type.":' ".$ar->BIB_dc_title[0]->BIB_dc_title_value." {".$ar->BIB_dc_identifier_index."}"
      );
    }
    return $result;
  }

  public function saveRelation($data)
  {
    $search = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
    ->where('mag_relation_FK_document_id',$data->__id)
    ->first();
    $r = ($search) ? $search : org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Relations');

    //estraggo l'intervallo complessivo rappresentato dalla stru logicalStru
    $stru = json_decode($data->logicalStru);
    $interval = $this->getInterval($stru);

    //Se sto creando un padre, allora setto parent = 0
    if($data->flagParent != 'false')
    {
      $parent = 0;
    }
    //altrimenti devo cercare un padre per il mag
    else
    {
      $ar = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
      ->where('mag_relation_stru_id',$data->linkedStru['id'])
      ->where('mag_relation_parent',0)->first();
      if($ar)
      {
        $parent = $ar->mag_relation_id;
      }
      else
      {
        //Nel caso in cui si stiano creando prima i figli vestiti,
        //poi il padre, farà "fede" per la creazione delle relazioni
        //l'id della strumag collegata, quindi si imposta per ora
        //parent == null
        $parent = NULL;
      }
    }

    $r->mag_relation_FK_document_id = $data->__id;
    $r->mag_relation_stru_id = $data->linkedStru['id'];
    $r->mag_relation_parent = $parent;
    $r->mag_relation_interval = $interval;

    if($data->BIB_dc_title)
    {
      $r->mag_relation_title = $data->BIB_dc_title[0]->BIB_dc_title_value;
    }

    if($parent == 0)
    {
      if($data->flagVestito == 'false')
      {
        //Cerco i figli (che dovrebbero essere vestiti)
        //TODO aggiungere un campo per il controllo?
        $childrenIterator = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
        ->where('mag_relation_stru_id',$data->linkedStru['id'])
        ->where('mag_relation_FK_document_id',$data->__id,'<>');
        //Controllo se gli intervalli dei figli coprono interamente il padre nudo
        $tmp = explode("-",$interval);
        $intervalDiff = $tmp[1];
        $totalDiff = 0;
        //Controllo se c'è copertura totale nei figli vestiti creati
        foreach ($childrenIterator as $children) {
          $arrayInterval = explode("-",$children->mag_relation_interval);
          $totalDiff += $arrayInterval[1] - $arrayInterval[0] + 1;
        }
        //Se c'è copertura completa allora posso generare la relazione,
        //altrimenti dovrò segnalare all'utente che deve completare
        //la creazione dei figli vestiti
        if($intervalDiff == $totalDiff)
        {
          $id = $r->save();
          //Inserisco il valore del padre per i figli vestiti
          foreach ($childrenIterator as $children) {
            $children->mag_relation_parent = $id;
            $children->save();
          }
          return true;
        }
        else
        {
          return false;
        }
      }
    }

    $r->save();
    return true;
  }

  public function deleteRelation($data,$id=null)
  {
    $dataId = ($id) ? $id : $data->__id;
    $ar = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
    ->where('mag_relation_FK_document_id',$dataId)
    ->first();
    if($ar)
    {
      $relationId = $ar->mag_relation_id;
      $ar->delete();
      //Cancello la relazione e le eventuali figlie se sto cancellando una madre
      //settando a null il valore del mag_relation_parent
      $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
      ->where('mag_relation_parent',$relationId);
      foreach ($it as $ar) {
        $ar->mag_relation_parent = null;
        $ar->save();
      }
    }
  }

  public function getParent($id)
  {
    $ar = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
    ->where('mag_relation_id',$id)->first();

    $parent = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
    ->where('mag_relation_id',$ar->mag_relation_parent)->first();

    return $parent;
  }

  public function getInterval($stru)
  {
    foreach ($stru as $key => $value) {
      if(!is_int($start))
      {
        $start = $value->start;
      }
      if($value->stop)
      {
        $stop = $value->stop;
      }
    }
    return $start.'-'.$stop;
  }

  public function generateDCRelation($data)
  {
    //Recupero le relazioni relative al record
    $ar = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
    ->where('mag_relation_FK_document_id',$data->__id)->first();
    if($ar)
    {
      //Esempi di relazioni
      $flagVestito = ($data->flagVestito != 'true') ? false : true;
      $dcRelation = array();
      if($ar->mag_relation_parent == 0){
        //'contiene:' titolo {id}
        //Questo tipo di relazioni va impostata solo per i MAG padre NUDI
        if(!$flagVestito)
        {
          $children = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
          ->where('mag_relation_parent',$ar->mag_relation_id);
          foreach ($children as $child) {
            $cl = new stdClass();
            $child = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Model');
            $child->load($child->mag_relation_FK_document_id, 'PUBLISHED_DRAFT');
            $cl->BIB_dc_relation_value = array("id"=>$child->document_id,"text"=>"'contiene:' ".$child->BIB_dc_title[0]->BIB_dc_title_value." {".$child->BIB_dc_identifier_index."}");
            $dcRelation[] = $cl;
          }
        }
      }
      else{
        //'fa parte di:' titolo {id}
        //Questo tipo di relazioni va impostata solo per i MAG figli NUDI
        if(!$flagVestito)
        {
          $parentMag = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
          ->where('mag_relation_id',$ar->mag_relation_parent)->first();
          $parent = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Model');
          $parent->load($parentMag->mag_relation_FK_document_id, 'PUBLISHED_DRAFT');
          $cl = new stdClass();
          $cl->BIB_dc_relation_value = array("id"=>$parent->document_id,"text"=>"'fa parte di:' ".$parent->BIB_dc_title[0]->BIB_dc_title_value." {".$parent->BIB_dc_identifier_index."}");
          $dcRelation[] = $cl;
        }
      }
    }
    return $dcRelation;
  }
}
