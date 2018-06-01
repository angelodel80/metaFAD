<?php
class metafad_sbn_modules_sbnunimarc_controllers_ajax_ResynchKardex extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($url)
    {
        $kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');
        $kardexService->resynchData($url);
    }
}
