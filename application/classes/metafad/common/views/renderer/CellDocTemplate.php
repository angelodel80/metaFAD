<?php
class metafad_common_views_renderer_CellDocTemplate extends GlizyObject
{
	function renderCell($key, $value)
	{
		if ($value==1) $value = '<span class="'.__Config::get('glizy.datagrid.checkbox.on').'"></span>';
		else $value = '<span class="'.__Config::get('glizy.datagrid.checkbox.off').'"></span>';
		return $value;
	}
}
