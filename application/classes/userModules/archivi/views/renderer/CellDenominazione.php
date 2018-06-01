<?php
class archivi_views_renderer_CellDenominazione extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    function renderCell($key, $value, $row, $columnName, $doc)
    {
        $output = $value;

        if ($doc->parents_ss) {
            $output .= '</br><span class="small">'.implode('/', $doc->parents_ss).'</span>';
        }
        
        return $output;
    }
}



