<?php
set_time_limit(0);

class metafad_teca_mets_models_proxy_MetsProxy extends metafad_common_models_proxy_SolrQueueProxy
{
    public function save($data, $draft = false)
    {
        if (!empty($data->dc)) {
            if(!empty($data->dc[0]->BIB_dc_identifier))
            {
                $data->identifier = $data->dc[0]->BIB_dc_identifier[0]->BIB_dc_identifier_value;
            }
            if (!empty($data->dc[0]->BIB_dc_title))
            {
                $data->title = $data->dc[0]->BIB_dc_title[0]->BIB_dc_title_value;
            }
        }

        $data->isValid = 0;

        if ($data->linkedStru) {
            $data->linkedStru = array("id" => $data->linkedStru->id, "text" => $data->linkedStru->text);
        }

        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $result = $contentproxy->saveContent($data, __Config::get('glizycms.content.history'), $draft);

        if (!$data->__id) {
            $data->__id = $result['__id'];
        }

        $this->sendDataToSolr($data, $result['document']);

        $title = ($data->title) ?:'Senza titolo';
        $application = org_glizy_ObjectValues::get('org.glizy', 'application');
        $docStruProxy = $application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
        $rootId = $docStruProxy->saveNewRoot($data->__id, $title);
        
        return array('set' => $result, 'id' => $data->__id, 'rootId'=>$rootId);

    }

    public function delete($id, $model)
    {
        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $tmp = $contentproxy->loadContent($id, $model);
        $idStruMag = $tmp['linkedStru']->id;

        //Mi occupo anche della cancellazione dalla tabella docstru_tbl e dei figli generati nella pubblicazione
        $docStruProxy = $this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
        $rootNode = $docStruProxy->getRootNodeByDocumentId($id);
        $docStruProxy->deleteNode($rootNode->docstru_id, true, 'mets');

        //Cancellazione
        $contentproxy->delete($id, $model);

        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);
    }
}