<?php
class metafad_teca_STRUMAG_views_renderer_CellEditDeleteEcommerce extends metafad_common_views_renderer_CellEditDraftDelete
{

  function renderCell($key, $value, $row)
  {
    parent::renderCell($key, $value, $row,$columnName,$doc);
    $draft = ($row->hasPublishedVersion) ? $row->hasDraftVersion : true;

    $output = $this->renderEditButton($key, $row, $row->hasPublishedVersion).
    $this->renderEcommerceButton($key, $row, true).
    $this->renderDeleteButton($key, $row);

    return $output;
  }

  protected function renderEcommerceButton($key, $row, $enabled = true)
  {
      $output = '';
      if ($this->canView && $this->canEdit) {
          $output = __Link::makeLinkWithIcon(
              'actionsMVC',
              __Config::get('glizy.datagrid.action.ecommerceCssClass').($enabled ? '' : ' disabled'),
              array(
                  'title' => 'Ecommerce',
                  'id' => $key,
                  'action' => 'ecommerce',
                  'cssClass' => ($enabled ? '' : ' disabled-button')
              )
          );
      }

      return $output;
  }
}
