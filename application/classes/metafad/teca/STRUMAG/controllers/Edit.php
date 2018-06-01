<?php
class metafad_teca_STRUMAG_controllers_Edit extends org_glizycms_contents_controllers_moduleEdit_Edit
{
    public function execute($id)
    {
        $c = $this->view->getComponentById('__model');
        __Request::set('model', $c->getAttribute('value'));
        
        return parent::execute($id);
    }
}