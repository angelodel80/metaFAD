<?php
class metafad_gestioneDati_sbnaut_controllers_Index extends metafad_common_controllers_Command
{
    public function execute()
    {
      $formtype = $this->view->getComponentById('type');
      $formtype->setAttribute('value',__Request::get('formtype'));
      $version = $this->view->getComponentById('version');
      $version->setAttribute('value',__Request::get('version'));
    }
}
