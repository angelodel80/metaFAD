<?php
class metafad_common_views_renderer_CellEditDelete extends metafad_common_views_renderer_CellEditDraftDelete
{
  function renderCell($key, $value, $row)
  {
    parent::renderCell($key, $value, $row);
    $draft = ($row->hasPublishedVersion) ? $row->hasDraftVersion : true;

    $output = $this->renderEditButton($key, $row, $row->hasPublishedVersion).
    $this->renderDeleteButton($key, $row) ;

    return $output;
  }
}
