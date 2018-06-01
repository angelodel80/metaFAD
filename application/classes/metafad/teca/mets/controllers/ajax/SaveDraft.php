<?php
class metafad_teca_mets_controllers_ajax_SaveDraft extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionAndInstitute('editDraft', $data);
        if (is_array($result)) {
            return $result;
        }

        $metsProxy = __ObjectFactory::createObject('metafad.teca.mets.models.proxy.MetsProxy');

        $objData = json_decode($data);
        $isNew = $objData->__id == '';

        $result = $metsProxy->save($objData, true);

        if ($result['set']) {
            metafad_gestioneDati_boards_Common::logAction($isNew, __Request::get('pageId'), 'editDraft', $result['set']['document'], $objData->__id, 'scheda METS');
        }

        $this->directOutput = true;
        return $result;
    }

}
