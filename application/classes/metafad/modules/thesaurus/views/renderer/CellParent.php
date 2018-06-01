<?php
// TODO
// fare una superclasse processCell
// e una renderCell

// TODO rinominare in CellIsChecked
class metafad_modules_thesaurus_views_renderer_CellParent extends GlizyObject
{
	function renderCell($key, $value,$row)
	{
	    $document = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.ThesaurusDetails');

	    $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusDetails');
        $it->where('thesaurusdetails_id', $value);

        foreach ($it as $ar) {
					//TODO Valutare poi se mostrare key o value
            $parent = $ar->getRawData()->thesaurusdetails_key;
        }

        $value = '<div class="thesaurusParent" id="thesaurusParent-'.$row->thesaurusdetails_id.'" onchange="saveParent($(this));" data-key="'.$row->thesaurusdetails_id.'" data-val="' . $parent . '"></div>';
				return $value;
	}
}
