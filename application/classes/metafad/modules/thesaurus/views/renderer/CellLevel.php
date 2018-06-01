<?php
// TODO
// fare una superclasse processCell
// e una renderCell

// TODO rinominare in CellIsChecked
class metafad_modules_thesaurus_views_renderer_CellLevel extends GlizyObject
{
	function renderCell($key, $value,$row)
	{
			$output = '';
			for($i=1;$i<=5;$i++)
			{
				$class = ($value == $i) ? 'level selected':'level' ;
				$output .= '<span class="'.$class.'"><input type="button" style="border:none; padding: 0px; background:none; box-shadow: none;" data-id="'.$row->thesaurusdetails_id.'" value="'.$i.'" onClick="clickLevelAndSave($(this));"></input></span>';
			}
		return $output;
	}
}
