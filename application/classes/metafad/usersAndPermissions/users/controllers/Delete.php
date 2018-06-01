<?php
class metafad_usersAndPermissions_users_controllers_Delete extends org_glizycms_contents_controllers_activeRecordEdit_Delete
{
    public function execute($id, $model)
    {
        $relationsProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');
        $relationsProxy->delete($id);
        
        parent::execute($id, $model);
    }
}