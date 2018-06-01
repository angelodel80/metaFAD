<?php
class metafad_common_views_renderer_CellEditDeleteSS extends metafad_common_views_renderer_CellEditDraftDelete
{
  protected function renderDeleteButton($key, $row)
  {
        $modelName = $row->form->id.'.models.Model_'.str_replace(" ","_",strtolower($row->name));
        $countRecords = org_glizy_objectFactory::createModelIterator($modelName)
              ->setOptions(array('type' => 'PUBLISHED_DRAFT'))->count();
        $output = '';
        $message = ($countRecords == 0) ? 'Cancellare il modello di scheda semplificata? (non ci sono record salvati per questo tipo di scheda)':'Attenzione, verrano cancellate '.$countRecords.' schede relative a questa versione semplificata. Proseguire?';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon( 'actionsMVCDelete',
                                                            __Config::get('glizy.datagrid.action.deleteCssClass'),
                                                            array(
                                                                'title' => __T('GLZ_RECORD_DELETE'),
                                                                'id' => $key,
                                                                'model' => 'metafad.gestioneDati.schedeSemplificate.models.Model',
                                                                'action' => 'delete'  ),
                                                            $message);
        }

    return $output;
  }

  function renderCell($key, $value, $row)
  {
    $output = $this->renderEditButton($key, $row, true).
    $this->renderDeleteButton($key, $row) ;

    return $output;
  }
}
