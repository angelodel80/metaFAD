<?php
class metafad_gestioneDati_massiveEdit_controllers_ajax_StoreIds extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($ids)
    {
        $hash = md5(date("Y-m-d H:i:s"));
        __Session::set($hash,$ids);
        return $hash;
    }
}