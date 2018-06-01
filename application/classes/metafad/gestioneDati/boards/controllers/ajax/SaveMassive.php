<?php
class metafad_gestioneDati_boards_controllers_ajax_SaveMassive extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }

        $decodeData = json_decode($data);
        $draftPublished = 'PUBLISHED';
        $inverseDraftPublished = 'DRAFT';

        $ids = explode("-", $decodeData->__id);
        $model = $decodeData->__model;
        $arrayFieldToSkip = array('__id', '__model', 'isTemplate', 'popup', '__groupId', 'groupName');
        $fieldToSave = array();

        foreach($decodeData as $key => $value) {
            if (!in_array($key, $arrayFieldToSkip)) {
                if ($value != null) {
                    $fieldToSave[$key] = $value;
                }
            }
        }

        $proxy = new metafad_gestioneDati_boards_models_proxy_ICCDProxy();

        foreach($ids as $id) {
            $ar = org_glizy_ObjectFactory::createModel($model);
            $ar->load($id, $draftPublished);
            //Devo controllare che esista effettivamente la possibilità di salvare
            //il record nello stato selezionato... se non esiste una bozza devo salvare
            //in pubblica e viceversa...
            //Se un documento in quello stato non esiste:
            if (!$ar->document_id) {
                //Carico l'altra versione (che deve obbligatoriamente esistere)
                $ar->load($id, $inverseDraftPublished);
            }

            $data = $ar->getRawData();
            foreach($fieldToSave as $key => $value) {
                $data->$key = $value;
            }
            $data->__id = $ar->document_id;
            $data->__model = $model;

            $proxy->save($data);

            unset($ar);
        }
    }
}