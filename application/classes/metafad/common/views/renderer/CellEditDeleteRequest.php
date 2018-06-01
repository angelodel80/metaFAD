<?php
class metafad_common_views_renderer_CellEditDeleteRequest extends metafad_common_views_renderer_AbstractCellEdit
{
  function renderCell($key, $value, $row)
  {
    parent::renderCell($key, $value, $row);

    $output = $this->renderEditButton($key, $row, true).
    $this->renderDeleteSimpleButton($key, $row,'metafad.ecommerce.requests.models.Model') ;

    return $output;
  }
}
