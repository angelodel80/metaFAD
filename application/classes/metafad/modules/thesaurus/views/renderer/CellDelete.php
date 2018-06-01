<?php
// TODO
// fare una superclasse processCell
// e una renderCell

// TODO rinominare in CellIsChecked
class metafad_modules_thesaurus_views_renderer_CellDelete extends GlizyObject
{
	function renderCell($key, $value,$row)
	{
	    return '<a href="#" class="js-delete-row" data-id="'.$row->thesaurusdetails_id.'" title="Cancella"><i class="btn btn-danger btn-flat fa fa-trash"></i> </a>';
	}
}
