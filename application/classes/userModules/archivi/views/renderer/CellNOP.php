<?php
class archivi_views_renderer_CellNOP extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    function renderCell($key, $value, $row, $columnName, $doc)
    {
        return ($row->className == 'archivi.models.ComplessoArchivistico') ? '' : $value;
    }
}