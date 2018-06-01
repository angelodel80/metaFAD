<?php
class metafad_usersAndPermissions_institutes_controllers_Index extends org_glizycms_contents_controllers_activeRecordEdit_Edit
{
	public function execute()
	{
		$this->checkPermissionForBackend('visible');
		if(!__Config::get('metafad.be.hasInstitutes'))
		{
			$institute = org_glizy_objectFactory::createModelIterator('metafad.usersAndPermissions.institutes.models.Model');
			if($institute->count() > 0)
			{
				$instituteKey = $institute->first()->institute_key;
				metafad_usersAndPermissions_Common::setInstituteKey($instituteKey);
				$this->changePage('linkHome');
			}
		}

	}
}
