<?php
class metafad_sbn_modules_sbnunimarc_controllers_ajax_DetachStruMag extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($proxyParams)
    {
        $proxyParams = json_decode($proxyParams);
        
        $kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');
        $kardexService->detachStruMag($proxyParams->url, $proxyParams->fascicolo);
    }
}
