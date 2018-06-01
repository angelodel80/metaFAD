<?php
class archivi_views_renderer_CellLivelloDiDescrizione extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    function renderCell($key, $value, $row, $columnName, $doc)
    {
        return __T($value);
    }
}