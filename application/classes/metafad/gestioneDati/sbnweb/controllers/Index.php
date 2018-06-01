<?php
class metafad_gestioneDati_sbnweb_controllers_Index extends metafad_common_controllers_Command
{
    public function execute()
    {
      $formtype = $this->view->getComponentById('type');
      $formtype->setAttribute('value',__Request::get('formtype'));
    }
}
