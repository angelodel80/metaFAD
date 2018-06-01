<?php

class metafad_modules_thesaurus_views_renderer_Count extends GlizyObject
{
	function renderCell($key, $value, $row)
	{
			$it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.Details')
						->where('thesaurusdetails_FK_thesaurus_id',$value)->count();
			return $it;
	}
}
