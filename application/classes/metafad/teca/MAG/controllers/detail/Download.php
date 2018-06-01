<?php
class metafad_teca_MAG_controllers_detail_Download extends metafad_common_controllers_Command
{
  function execute($id)
  {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
    if ($id) {
      $exportHelper = org_glizy_ObjectFactory::createObject('metafad.teca.MAG.helpers.ExportHelper',$this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy'));
      $exportHelper->showXML($id);
    }
  }
}
