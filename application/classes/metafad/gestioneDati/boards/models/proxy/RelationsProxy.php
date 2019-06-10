<?php
class metafad_gestioneDati_boards_models_proxy_RelationsProxy extends GlizyObject
{
    public function processRelations($data)
    {
        $NCT = $data->NCTR.$data->NCTN.$data->NCTS;
        $relation_form = str_replace('.models.Model', '', $data->__model);

        $it = __ObjectFactory::createModelIterator('metafad.gestioneDati.boards.models.Relations')
            ->where('relation_title', $NCT)
            ->whereIsNull('relation_FK_related_document_id');

        foreach ($it as $ar) {
            $ar->relation_FK_related_document_id = $data->__id;
            $ar->relation_form = $relation_form;
            $ar->save();
        }

        $rv = $data->RV[0];
        if ($rv->RVE || $rv->RSE || $rv->ROZ) {
            $this->saveRelation($data);
        } else {
            $this->deleteRelations($data->__id);
        }
    }

  public function saveRelation($data)
  {
    //- Elimino tutte le relazioni precedenti di $document_id
    $document_id = $data->__id;
    $this->deleteRelations($document_id);
    $tsk = $data->TSK;
    $model = $data->__model;
    if($model == 'SchedaF400.models.Model')
    {
      $formF = true;
    }
    //Selezione del codice ("titolo") della scheda in questione
    if($data->RV[0]->RVE[0]->RVEL)
    {
      $RVEL = '-'.$data->RV[0]->RVE[0]->RVEL;
    }
    $title = $data->NCTR.$data->NCTN.$data->NCTS.$RVEL;
    $titleNoRVEL = $data->NCTR.$data->NCTN.$data->NCTS;
    if($title == '-' || $title == NULL)
    {
      $title = "Senza codice";
    }
    if($titleNoRVEL == NULL)
    {
      $titleNoRVEL = "Senza codice";
    }
    //- Per ogni $rse salvo le relazioni nuove
    if($data->RV[0]->RVE)
    {
      foreach ($data->RV[0]->RVE as $r) {
          $arr = (array)$r;
          if (!empty($arr)) {
            $relation = org_glizy_ObjectFactory::createModel( 'metafad.gestioneDati.boards.models.ComplexRelations' );
            $relation->complex_relation_FK_document_id = $document_id;
            $form = $this->getModelType($document_id);
            $relation->complex_relation_level = ($r->RVEL)?:0;
            $relation->complex_relation_form = $form;
            if($formF)
            {
              $relation->complex_relation_rver = $titleNoRVEL;
            }
            else if($relation->complex_relation_level == 0)
            {
              $relation->complex_relation_rver = $titleNoRVEL;
            }
            else {
              $relation->complex_relation_rver = $r->RVER;
            }
            if($relation->complex_relation_rver)
            {
              $relation->save();
            }
          }
      }
    }

    //- Per ogni $rse salvo le relazioni nuove
    if($data->RV[0]->RSE)
    {
      foreach ($data->RV[0]->RSE as $r) {
          $arr = (array)$r;
          if (!empty($arr)) {
            $relation = org_glizy_ObjectFactory::createModel( 'metafad.gestioneDati.boards.models.Relations' );
            $relation->relation_FK_document_id = $document_id;
            $form = $this->getModelType($r->RSEC->id);
            $relation->relation_FK_related_document_id = ($form) ? $r->RSEC->id : NULL;
            if(!$form)
            {
              $form = $r->RSET;
            }
            $rsecText = ($r->RSEC->text) ?  $r->RSEC->text : 'Senza codice';
            $relation->relation_title = $rsecText;
            $relation->relation_this_title = $title;
            $relation->relation_type = $r->RSER;
            $relation->relation_this_form = $this->getModelType($document_id);
            $relation->relation_form = $form;
            $relation->save();
          }
      }
    }
    //- Per ogni $roz salvo le relazioni nuove
    if($data->RV[0]->ROZ)
    {
      foreach ($data->RV[0]->ROZ as $r) {
        $relation = org_glizy_ObjectFactory::createModel( 'metafad.gestioneDati.boards.models.Relations' );
        $relation->relation_FK_document_id = $document_id;
        $form = $this->getModelType($r->{'ROZ-element'}->id);
        $relation->relation_FK_related_document_id = ($form) ? $r->{'ROZ-element'}->id : NULL;
        $relation->relation_title = $r->{'ROZ-element'}->text;
        $relation->relation_this_title = $title;
        $relation->relation_this_form = $this->getModelType($document_id);
        $relation->relation_form = $form;
        $relation->relation_roz = true;
        $relation->save();
      }
    }
  }

  public function getModelType($id)
  {
    $relation = org_glizy_ObjectFactory::createModelIterator( 'metafad.gestioneDati.boards.models.Relations' )
                ->load('getType', array('id' => $id))->first();
    return $relation->document_type;
  }

  public function deleteRelations($id)
  {
    $it = org_glizy_ObjectFactory::createModelIterator( 'metafad.gestioneDati.boards.models.Relations' )
          ->where('relation_FK_document_id',$id);
    foreach ($it as $ar) {
      $ar->delete();
    }
    $it = org_glizy_ObjectFactory::createModelIterator( 'metafad.gestioneDati.boards.models.ComplexRelations' )
          ->where('complex_relation_FK_document_id',$id);
    foreach ($it as $ar) {
      $ar->delete();
    }
  }
}
