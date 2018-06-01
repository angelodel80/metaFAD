<?php
class metafad_teca_STRUMAG_controllers_Ecommerce extends org_glizycms_contents_controllers_moduleEdit_Edit
{
  function execute($id)
  {
    $c = $this->view->getComponentById('strumagSection');
    $c->setAttribute('ecommerce',true);
    parent::execute($id);
  }
}
