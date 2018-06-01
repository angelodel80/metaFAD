<?php

class metafad_gestioneDati_boards_controllers_ajax_Validate extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        if ($data) {
            $result = $this->checkPermissionAndInstitute('publish', $data);
            if (is_array($result)) {
                return $result;
            }

            $c = $this->view->getComponentById('__model');
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');

            $result = $contentProxy->validate(json_decode($data), $c->getAttribute('value'));
            $this->directOutput = true;
            if ($result === true) {
                $updateInfo = new stdclass();
                $updateInfo->isValid = 1;
                $decodeData = json_decode($data);
                if($decodeData->__id){
                    //aggiornamento su SOLR
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
                    }

                    $decodeData->isValid = 1;

                    $decodeData->document = json_encode($cl);

                    $evt = array('type' => 'insertRecord', 'data' => array('data' => $decodeData, 'option' => array('commit' => true)));
                    $this->dispatchEvent($evt);
                    //termine aggiornamento SOLR
                }
                return array('success' => true);
            }
            else {
                return array('errors' => $result);
            }
        }
    }
}
