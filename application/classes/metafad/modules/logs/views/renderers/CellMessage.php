<?php
class metafad_modules_logs_views_renderers_CellMessage extends org_glizy_components_render_RenderCell
{
	function renderCell($key, $value, $item)
	{
		return str_replace("\n", '<br>', $value);
	}
}
