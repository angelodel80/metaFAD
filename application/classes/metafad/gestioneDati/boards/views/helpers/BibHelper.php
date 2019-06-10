<?php
class metafad_gestioneDati_boards_views_helpers_BibHelper extends GlizyObject
{
  public function getBibValues($bib)
  {
    //Sistema il testo mostrato nelle schede BIB collegate in F, S, D, ecc...
    $newBib = array();
    $dataBib = $bib;
    foreach ($dataBib as $value) {
      $doc = org_glizy_objectFactory::createModelIterator('metafad.gestioneDati.boards.models.Documents')
            ->where('document_id',$value->__BIB->id)->first();
      if($doc->document_type == '')
      {
        continue;
      }
      $ar = org_glizy_objectFactory::createModel($doc->document_type.'.models.Model');

      $ar->load($value->__BIB->id);

      $findTermFields = $ar->getFindTermFields();
      $text = array();
      foreach ($findTermFields as $field) {
        if(is_array($field))
        {
          $text[] .= ($ar->$field[1]) ? $field[0] . $ar->$field[1] : $ar->document_id;
        }
        else
        {
          $text[] .= glz_strtrim($ar->$field, 50);
        }
      }

      $value->__BIB->text = implode(' - ', array_filter($text));
      $newBib[] = $value;
    }
    return $newBib;
  }
}
