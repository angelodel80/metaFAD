<?php
class metafad_usersAndPermissions_institutes_views_renderer_CellInstitutesActive extends GlizyObject
{
	function renderCell($key, $value)
	{
		$class = $value ? __Config::get('glizy.datagrid.checkbox.on') : __Config::get('glizy.datagrid.checkbox.off');
		$output = '<span class="'.$class.'"></span>';
		return $output;
	}
}
