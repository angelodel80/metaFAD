<?php
class metafad_usersAndPermissions_roles_Acl extends org_glizy_application_Acl
{
    protected $roles;
    protected $aclMatrix;

    function __construct($id, $groupId)
    {
        parent::__construct($id, $groupId);

        $this->addEventListener('reloadAcl', $this);

        $this->aclMatrix = array();

        if ($id)  {
            // TODO ora la matrice è memorizzata nella sessione
            // e non può essere invalidata dal gestore dei ruoli per tutti gli utenti
            $roles = __Session::get('glizy.roles');
            if (!empty($roles)) {
                $this->roles = $roles;
                $this->aclMatrix = __Session::get('glizy.aclMatrix');
            } else {
                $this->reloadAcl();
            }
        }
    }

    public function reloadAcl()
    {
        if (!$this->id)  {
            $this->id = org_glizy_ObjectValues::get('org.glizy', 'userId');
            if (!$this->id) {
                return;
            }
        }

        if ($this->id) {
            $relationsProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');
            list($this->roles, $this->aclMatrix) = $relationsProxy->getPermissions($this->id);

            if (!empty($this->aclMatrix)) {
                __Session::set('glizy.roles', $this->roles);
                __Session::set('glizy.aclMatrix', $this->aclMatrix);
            }
        }
    }

    function acl($name, $action, $default=false)
    {
        if ($name == 'utenti-e-permessi-selezione-istituto' || $name == 'Home') {
            return true;
        }

        $name = $name=='*' ? strtolower($this->application->getPageId()) : strtolower($name);
        if (isset($this->aclMatrix[$name])) {
            $result = $this->aclMatrix[$name]['all'] || $this->aclMatrix[$name][strtolower($action)];
        } else {
            $result = is_null($default) ? false : $default;
        }
        return $result;
    }

    function inRole($roleId)
    {
        return $this->roles[$roleId];
    }

    function getRoles()
    {
        return array_keys($this->roles);
    }

    function invalidateAcl()
    {
        __Session::set('glizy.roles', null);
        __Session::set('glizy.aclMatrix', null);
    }
}