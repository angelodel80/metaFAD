<?php
class metafad_usersAndPermissions_users_controllers_ajax_Save extends org_glizycms_contents_controllers_activeRecordEdit_ajax_Save
{
    public function execute($data)
    {
        $this->directOutput = true;
        $data = json_decode($data);
        $userProxy = org_glizy_objectFactory::createObject('metafad.usersAndPermissions.users.models.proxy.UsersProxy');
        $beUser = $userProxy->isBEuserGroup($data->user_FK_usergroup_id);


        if ($beUser && empty($data->instituteAndRole)) {
            $result = array(
                'errors' => array('Aggiungi almeno un istituto')
            );

            return $result;
        }

        if ($data->__id) {
            $ar = org_glizy_ObjectFactory::createModel('metafad.usersAndPermissions.users.models.Model');
            $ar->load($data->__id);
            if ($ar->user_password !== $data->user_password) {
                $data->user_password = glz_password($data->user_password);
            }
        } else {
            $data->user_password = glz_password($data->user_password);
        }

        $result = parent::execute($data);

        if ($beUser) {
            $relationsProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');
            $relationsProxy->save($result['set']['__id'], $data->instituteAndRole);
        }

        return $result;
    }
}