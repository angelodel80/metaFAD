<?php
class metafad_mods_controllers_ajax_SaveDraft extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionAndInstitute('editDraft', $data);
        if (is_array($result)) {
            return $result;
        }

        /**
         * @var $proxy metafad_mods_models_proxy_ModuleProxy
         */
        $proxy = __ObjectFactory::createObject("metafad.mods.models.proxy.ModuleProxy");
        $this->directOutput = true;
        $result = $proxy->saveDraft(json_decode($data));

        return $result;
    }


}