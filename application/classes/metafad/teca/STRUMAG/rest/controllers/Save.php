<?php
class metafad_teca_STRUMAG_rest_controllers_Save extends org_glizy_rest_core_CommandRest
{
    function execute($id)
    {
        $this->checkPermissionForBackend();
        $isNew = (!$id) ? true : false;
        $body = json_decode(__Request::getBody());
        $now = new org_glizy_types_DateTime();
        $ar = __objectFactory::createModel('metafad.teca.STRUMAG.models.Model');
        $ar->load($id);
        $ar->state = $body->state;
        $ar->title = $body->title;
        $ar->physicalSTRU = json_encode($body->physicalSTRU);
        $ar->logicalSTRU = json_encode($body->logicalSTRU);
        $id = $ar->publish();

        $decodeData = (object)$ar->getValuesAsArray();

        $cl = new stdClass();

        $it = org_glizy_ObjectFactory::createModelIterator( 'metafad.teca.STRUMAG.models.Model' );

        if ($it->getArType() === 'document') {
            $it->setOptions(array('type' => 'PUBLISHED_DRAFT'));
        }

        $it->where('document_id', $id, 'ILIKE');
        foreach ($it as $record) {
            $cl->className = $record->getClassName(false);
            $cl->isVisible = $record->isVisible();
            $cl->isTranslated = $record->isTranslated();
            $cl->hasPublishedVersion = $record->hasPublishedVersion();
            $cl->hasDraftVersion = $record->hasDraftVersion();
            $cl->document_detail_status = $record->getStatus();
        }

		$decodeData->physicalSTRU = $ar->physicalSTRU;
		$decodeData->logicalSTRU = $ar->logicalSTRU;

		$decodeData->__id = $id;
        $decodeData->__model = 'metafad.teca.STRUMAG.models.Model';
        $decodeData->instituteKey = $ar->instituteKey ?: metafad_usersAndPermissions_Common::getInstituteKey();
        $decodeData->document = json_encode($cl);
        
        metafad_gestioneDati_boards_Common::logAction($isNew, 'teca-STRUMAG', 'edit', $ar, $id, 'STRUMAG');

        $decodeData->__commit = true;
        $evt = array('type' => 'insertRecord', 'data' => array('data' => $decodeData, 'option' => array('commit' => true)));
        $this->dispatchEvent($evt);

        $vo = __objectFactory::createObject('metafad_teca_STRUMAG_models_vo_STRUMAGVO', $ar);
        return $vo;
    }
}
