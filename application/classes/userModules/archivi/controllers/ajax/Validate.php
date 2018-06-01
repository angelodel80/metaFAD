<?php
class archivi_controllers_ajax_Validate extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        if ($data) {
            $result = $this->checkPermissionAndInstitute('publish', $data);
            if (is_array($result)) {
                return $result;
            }

            /**
             * @var $proxy archivi_models_proxy_ArchiviProxy
             */
            $proxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
            $result = $proxy->validate(json_decode($data));
            $this->directOutput = true;
            return $result;
        }
    }
}