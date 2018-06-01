<?php
class metafad_usersAndPermissions_users_models_proxy_UsersProxy extends GlizyObject
{
    public function loadUser($userId)
    {
        $ar = __ObjectFactory::createModel('metafad.usersAndPermissions.users.models.Model');
        $ar->load($userId);
        return $ar;
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.usersAndPermissions.users.models.Model');

        if ($term != '') {
            $filter = array('__OR__' => array());
            $filter['__OR__']['user_firstName'] = array('value' => '%'.$term.'%', 'condition' => 'LIKE');
            $filter['__OR__']['user_lastName'] = array('value' => '%'.$term.'%', 'condition' => 'LIKE');
            $it->setFilters($filter);
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->user_firstName . ' ' . $ar->user_lastName,
            );
        }

        return $result;
    }

    /**
     * Check if the groupID has backend Access
     * @return boolean [description]
     */
    public function isBEuserGroup($userGroupId)
    {
        $arGroup = __ObjectFactory::createModel('org.glizycms.groupManager.models.UserGroup');
        $arGroup->load($userGroupId);
        return $arGroup->usergroup_backEndAccess==1;
    }
}