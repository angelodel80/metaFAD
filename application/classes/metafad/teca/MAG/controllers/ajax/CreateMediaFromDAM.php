<?php
class metafad_teca_MAG_controllers_ajax_CreateMediaFromDAM extends metafad_common_controllers_ajax_CommandAjax
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
        
        $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Img')
              ->where('docstru_rootId', $rootId->docstru_id)
              ->where('docstru_type', 'Img');

        $docStruProxy->saveNewMedia($mediaDecoded, $rootId->docstru_id, $it->count()+1);

        return array('sendOutput' => 'fileTabs', 'sendOutputState' => 'edit', 'sendOutputFormat' => 'html');
    }
}
