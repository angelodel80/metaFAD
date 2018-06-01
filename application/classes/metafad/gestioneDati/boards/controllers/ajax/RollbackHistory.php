<?php

class metafad_gestioneDati_boards_controllers_ajax_RollbackHistory extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($model, $id, $vid)
    {
        $this->checkPermissionForBackend();

        $it = org_glizy_objectFactory::createModelIterator($model);
        $it->where("document_detail_id", $vid)
           ->allStatuses();
        $ar = $it->first();

        $data = json_decode($ar->document_detail_object);
        if (!is_object($data)) {
            return false;
        }

        $data->__id = $id;
        $data->__model = $model;
        $iccdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy');
        return $iccdProxy->save($data);
    }
}
