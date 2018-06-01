<?php
class metafad_usersAndPermissions_roles_controllers_Edit extends org_glizycms_contents_controllers_moduleEdit_Edit
{
    public function execute($id)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'));
            $users = array();
            $it = org_glizy_objectFactory::createModelIterator('org.glizycms.roleManager.models.User', 'getUsers', array('params' => array('roleId' => $id)));
            foreach ($it as $ar) {
                $users[] = array(
                    'id' => $ar->join_FK_dest_id,
                    'text' => $ar->user_firstName . ' ' . $ar->user_lastName
                );
            }
            $data['users'] = $users;
            $data['__id'] = $id;
            $this->view->setData($data);
        }
    }
}