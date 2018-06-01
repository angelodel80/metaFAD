<?php

class metafad_modules_thesaurus_views_renderer_Form extends GlizyObject
{
	function renderCell($key, $value, $row)
	{
	    return $row->forms;
	}
}
