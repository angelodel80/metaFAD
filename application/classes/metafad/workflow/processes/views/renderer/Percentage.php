<?php

class metafad_workflow_processes_views_renderer_Percentage extends GlizyObject
{
    function renderCell($key, $value, $row)
    {
        if (!$value && $value !== 0) {
            return null;
        }
        $output = '<input style="position: relative; float: left;" type="text" value="' . $value . '" class="knob" data-width="25" data-height="25" data-readOnly="true" data-displayInput="false" data-thickness="0.3" data-fgColor="#f39c12">';
        $output .= '<label for="knob">' . $value . '%</label>';
        return $output;
    }
}