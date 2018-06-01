<?php

class metafad_usersAndPermissions_users_controllers_Edit extends org_glizycms_contents_controllers_moduleEdit_Edit
{
    public function execute($id)
    {
        if ($id) {
            $userProxy = org_glizy_objectFactory::createObject('metafad.usersAndPermissions.users.models.proxy.UsersProxy');
            $relationsProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');
            $institutesAndRoles = $relationsProxy->load($id);

            $c = $this->view->getComponentById('__model');
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'));
            $data['__id'] = $id;

			$data['instituteAndRole']  = $institutesAndRoles;
            $this->view->setData($data);

            $this->setComponentsVisibility('instituteAndRole', $userProxy->isBEuserGroup($data['user_FK_usergroup_id']));
        }
    }

	//TODO Utilizzare per settare in automatico l'istituto
	public function setAutoInstitute()
	{
		$this->view->getComponentById('instituteAndRole')->setAttribute('data','type=repeat;repeatMin=1;repeatMax=1;');
		$this->view->getComponentById('institute')->setAttribute('readOnly','true');
		$institute = org_glizy_objectFactory::createModelIterator('metafad.usersAndPermissions.institutes.models.Model');
		if($institute->count() > 0)
		{
			$institute = $institute->first();
			$institutesAndRoles = array();
			$instituteObj = new stdClass();
			$obj = new stdClass();
			$obj->id = $institute->institute_key;
			$obj->text = $institute->institute_name;
			$instituteObj->institute = $obj;
			$institutesAndRoles[] = $instituteObj;
		}
		return $institutesAndRoles;
	}
}
