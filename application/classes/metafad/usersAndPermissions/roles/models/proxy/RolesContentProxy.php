<?php
class metafad_usersAndPermissions_roles_models_proxy_RolesContentProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = org_glizy_objectFactory::createModelIterator('org.glizycms.roleManager.models.Role');

        if ($term != '') {
            $it->where('role_name', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->role_name,
            );
        }

        return $result;
    }

    protected function addAclRelations($ar)
    {
        $ar->addRelation(array('type' => 'joinTable', 'name' => 'rel_aclEdit', 'className' => 'org.glizy.models.JoinDoctrine', 'field' => 'join_FK_source_id', 'destinationField' => 'join_FK_dest_id',  'bindTo' => '__aclEdit', 'objectName' => ''));
        $ar->addRelation(array('type' => 'joinTable', 'name' => 'rel_aclView', 'className' => 'org.glizy.models.JoinDoctrine', 'field' => 'join_FK_source_id', 'destinationField' => 'join_FK_dest_id',  'bindTo' => '__aclView', 'objectName' => ''));
        $ar->setProcessRelations(true);
    }

    public function saveContent($data)
    {
        $aclPageTypes = array();
        $permissions = array();

        foreach ($data as $k => $v) {
            if (strpos($k, 'permissions[') === 0) {
                preg_match('/permissions\[(.+)\]\[(.+)\]/', $k, $m);
                $pageId = $m[1];
                $action = $m[2];
                $permissions[$pageId][$action] = $v;
            }

            if (strpos($k, 'aclPageTypes[') === 0) {
                preg_match('/aclPageTypes\[(.+)\]/', $k, $m);
                $pageId = $m[1];
                $aclPageTypes[$pageId] = $v;
            }
        }

        foreach ($aclPageTypes as $masterPage => $pages) {
            $pages = explode(',', $pages);

            foreach ($pages as $page) {
                $page = strtolower($page);

                // se già sono settati permessi specifici non vengon copiati dal pagetype master
                if ($permissions[$page]) continue;

                // copia i permessi del pagetype master
                $permissions[$page] = $permissions[$masterPage];
            }
        }

        $roleId = $data->__id;

        $ar = org_glizy_ObjectFactory::createModel('org.glizycms.roleManager.models.Role');
        if ($roleId) {
            $ar->load($roleId);
        }
        $ar->role_name = $data->role_name;;
        $ar->role_active = $data->role_active;
        $ar->role_permissions = serialize($permissions);
        $roleId = $ar->save();

        $ar = org_glizy_ObjectFactory::createModel('org.glizy.models.Join');
        $ar->delete(array('join_FK_source_id' => $roleId, 'join_objectName' => 'roles2usergroups'));
        $ar->delete(array('join_FK_source_id' => $roleId, 'join_objectName' => 'roles2users'));

        $users = array();

        foreach($data->users as $user){
            $users [] = $user->id;
        }

        if ($users){
            foreach ($users as $userId) {
                $ar->join_FK_source_id = $roleId;
                $ar->join_FK_dest_id = $userId;
                $ar->join_objectName = 'roles2users';
                $ar->save(null, true);
            }
        }
        
        $evt = array('type' => 'reloadAcl');
        $this->dispatchEvent($evt);
        
        return array('__id' => $roleId);
    }

    public function delete($recordId, $model)
    {
        $ar = org_glizy_objectFactory::createModel($model);

        if (__Config::get('ACL_MODULES')) {
            // permessi editing e visualizzazione record
            $this->addAclRelations($ar);
        }

        $ar->delete($recordId);
    }
}