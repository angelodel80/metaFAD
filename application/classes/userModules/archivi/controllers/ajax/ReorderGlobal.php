<?php
class archivi_controllers_ajax_ReorderGlobal extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id)
    {
        $archiviProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');
        $archiviProxy->queueReorderGlobal($id);
    }
}