<?php
class archivi_controllers_ajax_GetSteps extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id, $type)
    {
        $steps = array();
        
        $archiveProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');
        $docs = $archiveProxy->getNodesToReorder($id, $type);

        foreach ($docs as $i => $doc) {
            $params = array(
                'id' => $doc->id,
                'model' => $doc->document_type_t[0],
                'ordinamentoProvvisorio' => $i + 1
            );
            $steps[] = array('action' => 'ReorderNode', 'params' => $params);
        }

        $steps[] = array('action' => 'ReorderGlobal', 'params' => array('id'=> $id));

        $steps[] = array('action' => 'end', 'message' => 'Completato');

        return $steps;
    }
}