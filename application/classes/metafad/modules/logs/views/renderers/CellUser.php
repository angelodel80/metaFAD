<?php
class metafad_modules_logs_views_renderers_CellUser extends org_glizy_components_render_RenderCell
{
	function renderCell($key, $value, $item)
	{
		return $item->user_firstName.' '.$item->user_lastName;
	}
}
