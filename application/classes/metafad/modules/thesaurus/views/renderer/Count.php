<?php

class metafad_modules_thesaurus_views_renderer_Count extends GlizyObject
{
	function renderCell($key, $value, $row)
	{
		if(__Config::get('metafad.thesaurus.filterInstitute'))
		{
			$it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.Details');
			$it->load('instituteFilter', array('params' => array('thesaurusId' => $value, 'institute_key' => metafad_usersAndPermissions_Common::getInstituteKey())));
			return $it->count();
		}
		else
		{		
			$it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.Details')
				->where('thesaurusdetails_FK_thesaurus_id',$value);
			return $it->count();
		}
	}
}
