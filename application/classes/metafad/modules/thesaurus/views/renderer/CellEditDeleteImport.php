<?php
class metafad_modules_thesaurus_views_renderer_CellEditDeleteImport extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    function renderCell($key, $value, $row)
    {
        $this->loadAcl($key);
        $output = $this->renderEditButton($key, $row).
                    $this->renderDeleteButton($key, $row);
        return $output;
    }
}


