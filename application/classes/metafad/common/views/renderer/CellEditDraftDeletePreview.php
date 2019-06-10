<?php
class metafad_common_views_renderer_CellEditDraftDeletePreview extends metafad_common_views_renderer_AbstractCellEdit
{
    function renderCell($key, $value, $row, $columnName, $doc)
    {
        parent::renderCell($key, $value, $row);

        $draft = ($row->hasPublishedVersion) ? $row->hasDraftVersion : true;

        $output = $this->renderEditButton($key, $row, $row->hasPublishedVersion).
                  $this->renderEditDraftButton($key, $row, $draft).
                  $this->renderDeleteButton($key, $row).
                  $this->renderPreviewButton($key, $row) ;

        return $output;
    }
}
