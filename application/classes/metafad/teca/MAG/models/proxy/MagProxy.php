<?php
set_time_limit (0);

class metafad_teca_MAG_models_proxy_MagProxy extends metafad_common_models_proxy_SolrQueueProxy
{

    protected $application;

    function __construct()
    {
        $this->application = org_glizy_ObjectValues::get('org.glizy', 'application');
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.teca.MAG.models.Model');
        $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');

        if (metafad_usersAndPermissions_Common::getInstituteKey() != '*'){
            $it->where("instituteKey", metafad_usersAndPermissions_Common::getInstituteKey());
        }

        if ($term != '') {
            $it->where('title', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        $size = 0;
        $max = 25;
        foreach($it as $ar) {
            $decodeRelatedStru = json_decode($ar->relatedStru);
            $existsDocRel = $document->load($decodeRelatedStru->id);
            if(!$existsDocRel) {
                $result[] = array(
                    'id' => $ar->getId(),
                    'text' => $ar->title,
                );

                if (++$size >= $max) break;
            }
        }

        return $result;
    }

    public function modify($id, $data)
    {
        if ($this->validate($data)) {

            $document = $this->createModel($id, 'metafad.teca.MAG.models.Model');

            foreach ($data as $key => $value) {
                $document->$key = $value;
            }

            try {
                return $document->publish(null, null);
            } catch (org_glizy_validators_ValidationException $e) {
                return $e->getErrors();
            }
        } else {
            // TODO
        }
    }

    public function validate($data)
    {
        return true;
    }

    protected function createModel($id = null, $model)
    {
        $document = org_glizy_objectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }

    public function delete($id, $model, $deleteStrumag = true)
    {
        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $tmp = $contentproxy->loadContent($id, $model);
        $idStruMag = $tmp['linkedStru']->id;
        if ($idStruMag && $deleteStrumag) {
            $struMagProxy = __ObjectFactory::createObject('metafad.teca.STRUMAG.models.proxy.StruMagProxy');
            $struMagProxy->delete($idStruMag, false);
        }

        //Mi occupo anche della cancellazione dalla tabella docstru_tbl e dei figli generati nella pubblicazione
        $docStruProxy = $this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
        $rootNode = $docStruProxy->getRootNodeByDocumentId($id);
        $docStruProxy->deleteNode($rootNode->docstru_id);

        $relationProxy = __ObjectFactory::createObject('metafad.teca.MAG.models.proxy.MagRelationProxy');
        $relationProxy->deleteRelation(null,$id);
        $contentproxy->delete($id, $model);

        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);
    }

    public function save($data, $draft = false)
    {
        if ($data->dmdSec) {
            $data->identifier = $data->dmdSec[0]->BIB_dc_identifier[0]->BIB_dc_identifier_value;
            $data->title = $data->dmdSec[0]->BIB_dc_title[0]->BIB_dc_title_value;
        }

        if ($data->BIB_dc_identifier) {
            $data->BIB_dc_identifier_index = $data->BIB_dc_identifier[0]->BIB_dc_identifier_value;
        }

        $data->isValid = 0;

        if ($data->linkedStru) {
            $data->linkedStru = array("id" => $data->linkedStru->id, "text" => $data->linkedStru->text);
        }

        //Verifica condizioni
        $conditionHelper = org_glizy_objectFactory::createObject('metafad_teca_MAG_helpers_ConditionHelper');
        if(!$draft)
        {
            $errors = $conditionHelper->checkCondition($data);
        }

        //Eseguo salvataggio solo in caso di verifica delle condizioni
        if (empty($errors)) {
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $result = $contentproxy->saveContent($data, __Config::get('glizycms.content.history'), $draft);

            if (!$data->__id) {
                $data->__id = $result['__id'];
            }

            $this->appendDocumentToData($data);
            $this->sendDataToSolr($data, $result['document']);

            //Salvo docstru
            $title = $data->BIB_dc_title[0]->BIB_dc_title_value;
            $docStruProxy = $this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
            $docStruProxy->saveNewRoot($data->__id, $title);

            return array('set' => $result);
        } else {
            return array('errors' => $errors);
        }
    }
}