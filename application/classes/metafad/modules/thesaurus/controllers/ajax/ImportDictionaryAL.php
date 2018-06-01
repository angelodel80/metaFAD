<?php

class metafad_modules_thesaurus_controllers_ajax_ImportDictionaryAL extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }

        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);
        if ($_FILES) {
            $tmpFile = $_FILES[0]['tmp_name'];

            if (!file_exists($tmpFile)) {
                throw new Exception('Errore nel caricamento del file:' . $_FILES[0]['name']);
            } else {
                $inputFileName = $tmpFile;
            }

            $delAll = (__Request::get('Cancella_tutti') !== 'false');
            $replace = (__Request::get('Sostituisci_record') !== 'false');

            $importService = __ObjectFactory::createObject('metafad.modules.thesaurus.services.ImportService');
            $importService->importDictionaryFile($inputFileName, $delAll, $replace, "AL");
        } else {
            header('HTTP/1.1 412 File non importato');
        }
    }
}
