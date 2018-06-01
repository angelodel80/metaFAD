<?php
class metafad_mods_services_ModsService extends GlizyObject
{
    public function load($id, $model, $user, $status='PUBLISHED')
    {
        if ($id) {
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $model, $status);
            $data['__id'] = $id;

            $userProxy = org_glizy_objectFactory::createObject('metafad.usersAndPermissions.users.models.proxy.UsersProxy');
            $user = $userProxy->loadUser($data['document_detail_FK_user_id']);
            $data['compilatore'] = implode(' ', array($user->user_firstName, $user->user_lastName));
            $data['dataCreazione'] = $data['document_creationDate'];
            $data['dataModifica'] = $data['document_detail_modificationDate'];
        } else {
            $dateTime = new org_glizy_types_DateTime();
            $data = array(
                'compilatore' => implode(' ', array($user->firstName, $user->lastName)),
                'dataCreazione' => $dateTime->__toString()
            );
        }

        return $data;
    }
}