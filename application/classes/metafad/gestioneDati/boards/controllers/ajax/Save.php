<?php
class metafad_gestioneDati_boards_controllers_ajax_Save extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionAndInstitute('publish', $data);
        if (is_array($result)) {
            return $result;
        }

        $objData = json_decode($data);

        $isNew = $objData->__id == '';
        $type = explode('.', $objData->__model);
        
        /**
         * @var $iccdProxy metafad_gestioneDati_boards_models_proxy_ICCDProxy
         */
        $iccdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy');

        $result = $iccdProxy->save($objData);
        
        if ($result['set']) {
            metafad_gestioneDati_boards_Common::logAction($isNew, __Request::get('pageId'), 'edit', $result['set']['document'], $objData->__id, current($type));
        }

        $this->directOutput = true;
        return $result;
    }
}
