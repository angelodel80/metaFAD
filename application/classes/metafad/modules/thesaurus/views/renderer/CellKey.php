<?php
// TODO
// fare una superclasse processCell
// e una renderCell

// TODO rinominare in CellIsChecked
class metafad_modules_thesaurus_views_renderer_CellKey extends GlizyObject
{
	function renderCell($key, $value,$row)
	{
		$value = '<input id="iccd_theasaurus_key" data-type="key" data-id="'.$row->thesaurusdetails_id.'" name="iccd_theasaurus_key" onchange="editDataGrid($(this));" class="form-control" type="text" value="' . $value .'">';
		return $value;
	}
}
