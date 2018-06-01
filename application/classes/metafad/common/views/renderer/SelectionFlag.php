<?php

class metafad_common_views_renderer_SelectionFlag extends GlizyObject
{
    function renderCell( $key, $value, $row )
	{
		$output = '<input type="checkbox" id="flag_' . $key . '" class="selectionflag" data-id="'.$key.'"/>';

		return $output;
	}
}
