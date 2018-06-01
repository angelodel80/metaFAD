<?php
class metafad_teca_MAG_services_EventDebug extends org_glizy_mvc_core_Proxy implements metafad_teca_MAG_services_EventInterface
{
    public function insert($decodeData)
    {
        var_dump($decodeData);
    }
}
