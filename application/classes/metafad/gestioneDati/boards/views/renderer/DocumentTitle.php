<?php
class metafad_gestioneDati_boards_views_renderer_Subject extends org_glizycms_contents_views_renderer_DocumentTitle
{
    function renderCell( $key, $value, $docStore, $columnName, $row)
    {
        if ($columnName == 'SGTT_s') {
            return $row->SGTT_s ? $row->SGTT_s : $row->OGTD_t;
        }
        return $value;
    }
}