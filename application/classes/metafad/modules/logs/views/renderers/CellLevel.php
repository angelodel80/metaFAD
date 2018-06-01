<?php
class metafad_modules_logs_views_renderers_CellLevel extends org_glizy_components_render_RenderCell
{
	function renderCell($key, $value, $item)
	{
		$output = '';

        switch($value)
        {
            case "2" : $output = "[OPERAZIONE]";break;
            case "4" : $output = "[AZIONE]";break;
            case "16" : $output = "[ERRORE]";break;
        }

		return $output;
	}
}
