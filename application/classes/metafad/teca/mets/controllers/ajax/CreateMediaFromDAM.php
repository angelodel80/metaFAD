<?php
class metafad_teca_mets_controllers_ajax_CreateMediaFromDAM extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($media, $id)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }
        
      //Estraggo informazioni su docstru
        $docStruProxy = $this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
        $rootId = $docStruProxy->getRootNodeByDocumentId($id);
        $mediaDecoded = json_decode($media);

        $docStruProxy->saveNewMedia($mediaDecoded, $rootId->docstru_id, 1, 'mets');

        return array('sendOutput' => 'fileTabs', 'sendOutputState' => 'edit', 'sendOutputFormat' => 'html');
    }
}
