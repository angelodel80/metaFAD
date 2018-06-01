<?php
class metafad_gestioneDati_boards_views_components_RozRelations extends org_glizy_components_Component
{
    function init()
    {
        parent::init();
    }

    function render()
    {
        $id = __Request::get('id');
        $output .= '<ul class="entities">
                    <div class="title-relation">Altre relazioni (ROZ)</div>';
        //Seleziono tutte le relazioni di tipo ROZ della scheda in questione
        $roz = org_glizy_objectFactory::createModelIterator('metafad.gestioneDati.boards.models.Relations')
               ->where('relation_FK_document_id',$id)
               ->where('relation_roz',true);
        foreach ($roz as $r) {
          if($r->relation_FK_related_document_id == null)
          {
            $output .= '<div class="relationsDiv-roz">';
            $principalClass = ($r->relation_FK_related_document_id == $r->relation_FK_document_id) ? 'principal' : '';
            $relatedForm = $r->relation_this_form;
            $tsk = org_glizy_Modules::getModule($relatedForm)->iccdModuleType;
            $relatedForm = ($relatedForm == null) ? 'Non specificato' : $relatedForm;
            $tsk = ($tsk) ?: $relatedForm;
            $output .=
              '<li>
                <h5 class="'.$principalClass.'">'.$r->relation_this_title.'</h5>
                <div class="relation-type">TSK: '.$tsk.'</div>
                <div class="actions">';
            if($r->relation_FK_related_document_id != 0)
            {
              if($r->relation_FK_document_id != 0)
              {
                $link = __Link::makeUrl('linkEdit',array('pageId'=>$relatedForm,'id'=>$r->relation_FK_document_id));
              }
              $output .= '<a class="button-go" href="'.$link.'"><i class="fa fa-eye" aria-hidden="true"></i> Vedi</a>';
              $output .= '<a href="'.$link.'"><i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i> Vai</a>';
            }
            $output .= '</div></li>';
            $output .= '</div>';
          }
          else
          {
            $it = org_glizy_objectFactory::createModelIterator('metafad.gestioneDati.boards.models.Relations')
                  ->where('relation_FK_related_document_id',$r->relation_FK_related_document_id)
                  ->where('relation_roz',true);
            $output .= '<div class="relationsDiv-roz">';
            foreach ($it as $value) {
              $principalClass = ($value->relation_FK_related_document_id == $value->relation_FK_document_id) ? 'principal' : '';
              $relatedForm = $value->relation_this_form;
              $tsk = org_glizy_Modules::getModule($relatedForm)->iccdModuleType;
              $relatedForm = ($relatedForm == null) ? 'Non specificato' : $relatedForm;
              $tsk = ($tsk) ?: $relatedForm;
              $output .=
                '<li>
                  <h5 class="'.$principalClass.'">'.$value->relation_this_title.'</h5>
                  <div class="relation-type">TSK: '.$tsk.'</div>
                  <div class="actions">';
              if($value->relation_FK_related_document_id != 0)
              {
                if($value->relation_FK_document_id != 0)
                {
                  $link = __Link::makeUrl('linkEdit',array('pageId'=>$relatedForm,'id'=>$value->relation_FK_document_id));
                }
                $output .= '<a class="button-go" href="'.$link.'"><i class="fa fa-eye" aria-hidden="true"></i> Vedi</a>';
                $output .= '<a href="'.$link.'"><i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i> Vai</a>';
              }
              $output .= '</div></li>';
            }
            $output .= '</div>';
          }
        }


        $output .= '</ul>';
        $this->addOutputCode($output);
    }
}
