<?php
class metafad_teca_MAG_services_Event extends org_glizy_mvc_core_Proxy implements metafad_teca_MAG_services_EventInterface
{
    public function insert($decodeData)
    {
        $evt = array('type' => 'insertRecord', 'data' => array('data' => $decodeData, 'option' => array('commit' => true)));
        $this->dispatchEvent($evt);
    }
}
