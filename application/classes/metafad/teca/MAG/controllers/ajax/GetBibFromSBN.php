<?php
class metafad_teca_MAG_controllers_ajax_GetBibFromSBN extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($bid)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
      if(!$bid)
      {
        return array();
      }
      $helper = org_glizy_objectFactory::createObject('metafad.sbn.modules.sbnunimarc.model.proxy.SbnToMagProxy');
      return $helper->getMappedFieldObjects($bid);
    }
}
