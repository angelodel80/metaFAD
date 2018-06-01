<?php
class metafad_usersAndPermissions_institutes_controllers_Edit extends org_glizycms_contents_controllers_activeRecordEdit_Edit
{
    public function execute($id)
    {
        $c = $this->view->getComponentById('__model');
        __Request::set('model', $c->getAttribute('value'));

        return parent::execute($id);
    }
}