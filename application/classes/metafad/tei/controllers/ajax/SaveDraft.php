<?php
class metafad_tei_controllers_ajax_SaveDraft extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {   
        $result = $this->checkPermissionAndInstitute('editDraft', $data);
        if (is_array($result)) {
          return $result;
        }
        
        /**
         * @var $proxy metafad_tei_models_proxy_ModuleProxy
         */
        $proxy = __ObjectFactory::createObject("metafad.tei.models.proxy.ModuleProxy");
        $this->directOutput = true;
        $result = $proxy->saveDraft(json_decode($data));

        return $result;
    }
}