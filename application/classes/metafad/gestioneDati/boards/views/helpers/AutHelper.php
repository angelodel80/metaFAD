<?php
class metafad_gestioneDati_boards_views_helpers_AutHelper extends GlizyObject
{
  public function getAutValues($aut)
  {
    //Sistema il testo mostrato nelle schede AUT collegate in F, S, D, ecc...
    $newAut = array();
    $dataAut = $aut;
    foreach ($dataAut as $value) {
      $doc = org_glizy_objectFactory::createModelIterator('metafad.gestioneDati.boards.models.Documents')
            ->where('document_id',$value->__AUT->id)->first();
      if($doc->document_type == '')
      {
        continue;
      }
      $ar = org_glizy_objectFactory::createModel($doc->document_type.'.models.Model');
      $ar->load($value->__AUT->id);

      $findTermFields = $ar->getFindTermFields();
      $text = array();
      foreach ($findTermFields as $field) {
        if(is_array($field))
        {
          $text[] .= $field[0] . $ar->$field[1];
        }
        else
        {
          $text[] .= glz_strtrim($ar->$field, 50);
        }
      }
      $value->__AUT->text = implode(' - ', $text);
      $newAut[] = $value;
    }
    return $newAut;
  }
}
