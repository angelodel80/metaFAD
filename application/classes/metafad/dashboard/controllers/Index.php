<?php
class metafad_dashboard_controllers_Index extends metafad_common_controllers_Command
{
    public function execute()
    {
        $this->checkPermissionForBackend('visible');

      if(__Config::get('metafad.be.hasFE') != 'true')
      {
        $this->view->getComponentById('ls')->setAttribute('enabled',false);
        $this->view->getComponentById('lrRsl')->setAttribute('enabled',false);
        $this->view->getComponentById('widgets')->setAttribute('skin','metafad/dashboard/views/skins/Widgets_nofe.html');
      }
    }
}
