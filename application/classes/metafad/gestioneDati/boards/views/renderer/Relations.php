<?php
class metafad_gestioneDati_boards_views_renderer_Relations extends GlizyObject
{
    function renderCell( $ar, $value )
    {
      $relatedId = $ar->relation_FK_related_document_id;
      $ar->relation_type = (__T(__T($ar->relation_type)) == __T($ar->relation_type)) ? $ar->relation_type : __T($ar->relation_type);
      $relatedForm = $ar->relation_form;
      $tsk = org_glizy_Modules::getModule($relatedForm)->iccdModuleType;
      $relatedForm = ($relatedForm == null) ? 'Non specificato' : $relatedForm;
      $ar->tsk = ($tsk) ?: $relatedForm;
      $ar->title = $ar->relation_title;
      if($relatedId != 0)
      {
        $ar->link = __Link::makeUrl('linkEdit',array('pageId'=>$relatedForm,'id'=>$relatedId));
      }
    }
}
