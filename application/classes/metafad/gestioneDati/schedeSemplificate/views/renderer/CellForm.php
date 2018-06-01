<?php

class metafad_gestioneDati_schedeSemplificate_views_renderer_CellForm extends GlizyObject
{
	function renderCell($key, $value)
	{
		return $value->text;
	}
}
