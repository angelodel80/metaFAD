<?php
class metafad_sbn_modules_sbnunimarc_controllers_ajax_AttachStruMag extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($fieldName, $model, $query, $term, $proxy, $proxyParams, $getId, $data)
    {
        $proxyParams = json_decode($proxyParams);
        $struMagObj = (object) $data;
        
        $kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');
        $kardexService->attachStruMag($proxyParams->url, $proxyParams->fascicolo, $struMagObj);
    }
}
