<?php
class metafad_usersAndPermissions_relations_models_proxy_RelationsProxy extends GlizyObject
{
    public function delete($userId)
    {
        $model = __ObjectFactory::createModel('metafad.usersAndPermissions.relations.models.Model');
        $model->delete(array('user_roles_institutes_FK_userId' => $userId));
    }

    public function save($userId, $institutesRoles)
    {
        $this->delete($userId);

        $model = __ObjectFactory::createModel('metafad.usersAndPermissions.relations.models.Model');

        foreach ($institutesRoles as $instituteRole) {
            foreach ($instituteRole->roles as $role) {
                $model->user_roles_institutes_FK_userId = $userId;
                $model->user_roles_institutes_FK_instituteId = $instituteRole->institute->id;
                $model->user_roles_institutes_FK_roleId = $role->id;
                $model->save(null, true);
            }
        }

        $evt = array('type' => 'reloadAcl');
        $this->dispatchEvent($evt);
    }

    public function load($userId)
    {
        $it = __ObjectFactory::createModelIterator('metafad.usersAndPermissions.relations.models.Model')
            ->where('user_roles_institutes_FK_userId', $userId)
            ->orderBy('user_roles_institutes_FK_instituteId');

        $institutesRoles = array();

        $instituteRole = new StdClass();
        $institute = new StdClass();

        foreach ($it as $ar) {
            if ($institute->id != $ar->user_roles_institutes_FK_instituteId) {
                if ($instituteRole->institute) {
                    $institutesRoles[] = $instituteRole;
                }

                $instituteAr = org_glizy_objectFactory::createModel('metafad.usersAndPermissions.institutes.models.Model');
                $instituteAr->load($ar->user_roles_institutes_FK_instituteId);

                $institute = new StdClass();
                $institute->id = $ar->user_roles_institutes_FK_instituteId;

                $institute->text = $instituteAr->institute_name;

                $instituteRole = new StdClass();
                $instituteRole->institute = $institute;
                $instituteRole->roles = array();
            }

            $roleAr = org_glizy_objectFactory::createModel('org.glizycms.roleManager.models.Role');
            $roleAr->load($ar->user_roles_institutes_FK_roleId);

            $role = new StdClass();
            $role->id = $ar->user_roles_institutes_FK_roleId;
            $role->text = $roleAr->role_name;

            $instituteRole->roles[] = $role;
        }

        if ($instituteRole->institute) {
            $institutesRoles[] = $instituteRole;
        }

        return $institutesRoles;
    }

    public function hasMoreInstitutes($userId)
    {
        $count = __ObjectFactory::createModelIterator('metafad.usersAndPermissions.relations.models.Model')
               ->where('user_roles_institutes_FK_userId', $userId)
               ->groupBy('user_roles_institutes_FK_instituteId')
               ->count();

        return $count > 1;
    }

    public function getInstituteId($userId)
    {
        $ar = __ObjectFactory::createModel('metafad.usersAndPermissions.relations.models.Model');
        $ar->find(array('user_roles_institutes_FK_userId' => $userId));
        return $ar->user_roles_institutes_FK_instituteId;
    }

    /**
     * Ottiene la matrice dei permessi per l'utente
     */
    public function getPermissions($userId)
    {
        $roles = array();
        $aclMatrix = array();

        $instituteId = metafad_usersAndPermissions_Common::getInstituteId();

        if ($instituteId) {
            $it = __ObjectFactory::createModelIterator('metafad.usersAndPermissions.relations.models.Model')
                ->load('getPermissions', array('userId' => $userId, 'instituteId' => $instituteId));

            foreach ($it as $ar) {
                // se il ruolo non è attivo passa al prossimo
                if (!$ar->role_active) {
                    continue;
                }

                // se il ruolo non è stato ancora processato
                if (!$roles[$ar->role_id]) {
                    $roles[$ar->role_id] = true;
                    $permissions = unserialize($ar->role_permissions);
                    // unione delle matrici dei permessi
                    foreach ($permissions as $name => $actions) {
                        foreach ((array)$actions as $action => $value) {
                            $aclMatrix[strtolower($name)][strtolower($action)] |= $value;
                        }
                    }
                }
            }
        }


        return array($roles, $aclMatrix);
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $userId = org_glizy_ObjectValues::get('org.glizy', 'userId');

        $it = __ObjectFactory::createModelIterator('metafad.usersAndPermissions.relations.models.Model')
            ->load('institutesOfUser', array('userId' => $userId));

        if ($term != '') {
            $it->where('institute_name', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->institute_name,
                'key' => $ar->institute_key
            );
        }

        return $result;
    }

    public function getInstitutesOfCurrentUser()
    {
        $userId = org_glizy_ObjectValues::get('org.glizy', 'userId');

        $it = __ObjectFactory::createModelIterator('metafad.usersAndPermissions.relations.models.Model')
            ->load('institutesOfUser', array('userId' => $userId));

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->institute_name,
                'key' => $ar->institute_key
            );
        }

        return $result;
    }
}