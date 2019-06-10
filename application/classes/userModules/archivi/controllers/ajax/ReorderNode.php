<?php
class archivi_controllers_ajax_ReorderNode extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id, $model, $ordinamentoProvvisorio)
    {
        $archiviProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');
        $archiviProxy->reorderNode($id, $model, $ordinamentoProvvisorio);
    }
}