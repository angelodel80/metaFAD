<?php
// TODO
// fare una superclasse processCell
// e una renderCell

// TODO rinominare in CellIsChecked
class metafad_modules_thesaurus_views_renderer_CellValue extends GlizyObject
{
	function renderCell($key, $value,$row)
	{
		$value = '<input id="iccd_theasaurus_value" data-type="value" data-id="'.$row->thesaurusdetails_id.'" name="iccd_theasaurus_value" onchange="editDataGrid($(this));" class="form-control thesaurus_value" type="text" value="' . $value .'">';
		return $value;
	}
}
