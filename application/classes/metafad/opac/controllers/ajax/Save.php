<?php
class metafad_opac_controllers_ajax_Save extends org_glizycms_contents_controllers_moduleEdit_ajax_Save
{
    public function execute($data, $draft=false)
    {
        $decodeData = json_decode($data);
        $decodeData->isValid = 0;
        $data = json_encode($decodeData);

        $isNew = $decodeData->__id == '';

        $result = parent::execute($data, $draft);

        $decodeData->__id = $result['set']['__id'];

        if ($result['set']) {
            metafad_gestioneDati_boards_Common::logAction($isNew, __Request::get('pageId'), ((!$draft) ? 'edit' : 'editDraft'), $result['set']['document'], $decodeData->__id, 'maschera di ricerca');
        }


        $cl = new stdClass();

        $it = org_glizy_ObjectFactory::createModelIterator( $decodeData->__model );

        if ($it->getArType() === 'document') {
            $it->setOptions(array('type' => 'PUBLISHED_DRAFT'));
        }

        $it->where('document_id', $decodeData->__id, 'ILIKE');
        foreach ($it as $record) {
            $cl->className = $record->getClassName(false);
            $cl->isVisible = $record->isVisible();
            $cl->isTranslated = $record->isTranslated();
            $cl->hasPublishedVersion = $record->hasPublishedVersion();
            $cl->hasDraftVersion = $record->hasDraftVersion();
            $cl->document_detail_status = $record->getStatus();
            $cl->fields = $decodeData->fields;
        }

        $decodeData->document = json_encode($cl);

        $decodeData->__commit = true;
        $evt = array('type' => 'insertRecord', 'data' => array('data' => $decodeData, 'option' => array('commit' => true)));
        $this->dispatchEvent($evt);

        return $result;
    }

    protected function createModel($id = null, $model)
    {
        $document = org_glizy_objectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }

}
