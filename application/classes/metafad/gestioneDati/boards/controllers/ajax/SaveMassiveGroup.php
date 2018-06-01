<?php
class metafad_gestioneDati_boards_controllers_ajax_SaveMassiveGroup extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }

        $decodeData = json_decode($data);
        $decodeDataToSend = new stdClass();
        $model = 'metafad.gestioneDati.massiveEdit.models.Model';
        $ar = org_glizy_objectFactory::createModel($model);
        if ($decodeData->__groupId) {
            $ar->load($decodeData->__groupId);
        }
        $ar->routing = __Request::get('pageId');
        $ar->idList = $decodeData->__id;
        $ar->groupName = ($decodeData->groupName) ? $decodeData->groupName : 'Senza nome';
        $ar->institute_key = metafad_usersAndPermissions_Common::getInstituteKey();
        $ar->model = $decodeData->__model;
        $id = $ar->save(null, false, 'PUBLISHED');

        $result = array();
        $result['__groupId'] = $id;

        $cl = new stdClass();
        $cl->className = $model;
        $cl->isVisible = true;
        $cl->isTranslated = false;
        $cl->hasPublishedVersion = true;
        $cl->hasDraftVersion = false;
        $cl->document_detail_status = 'PUBLISHED';
        $cl->idList = $decodeData->__id;
        $cl->routing = __Request::get('pageId');

        $decodeDataToSend->doc_store = json_encode($cl);
        $decodeDataToSend->id = $id;
        $decodeDataToSend->groupName_t = $ar->groupName;
        $decodeDataToSend->idList_s = $ar->idList;
        $decodeDataToSend->routing_t = $ar->routing;
        $decodeDataToSend->model_t = $ar->model;
        $decodeDataToSend->document_type_t = $model;
        $decodeDataToSend->instituteKey_s = metafad_usersAndPermissions_Common::getInstituteKey();
        $decodeDataToSend->__commit = true;

        $evt = array('type' => 'insertData', 'data' => array('data' => $decodeDataToSend, 'option' => array('commit' => true)));
        $this->dispatchEvent($evt);

        $this->directOutput = true;

        return array('set' => $result);
    }
}
