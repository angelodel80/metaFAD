<?php
class archivi_controllers_ajax_Save extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionAndInstitute('publish', $data);
        if (is_array($result)) {
            return $result;
        }

        /**
         * @var $proxy archivi_models_proxy_ArchiviProxy
         */
        $proxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
        $this->directOutput = true;
        $result = $proxy->save(json_decode($data));

        return $result;
    }
}
