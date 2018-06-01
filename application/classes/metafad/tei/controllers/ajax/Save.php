<?php
class metafad_tei_controllers_ajax_Save extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionAndInstitute('publish', $data);
        if (is_array($result)) {
            return $result;
        }
        
        $proxy = __ObjectFactory::createObject("metafad.tei.models.proxy.ModuleProxy");
        $result = $proxy->save(json_decode($data));

        $this->directOutput = true;
        return $result;
    }
}
