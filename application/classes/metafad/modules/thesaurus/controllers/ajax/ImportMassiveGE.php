<?php
class metafad_modules_thesaurus_controllers_ajax_ImportMassiveGE extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }

        if ($_FILES) {
            $tmpFile = $_FILES[0]['tmp_name'];
            $dictName = $_FILES[0]['name'];

            if (!file_exists($tmpFile)) {
                throw new Exception('Errore nel caricamento del file:' . $_FILES[0]['name']);
            } else {
                $inputFileName = __Paths::getRealPath( 'CACHE' ).$dictName;
				move_uploaded_file($tmpFile, $inputFileName);
            }

            $jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
            $jobFactory->createJob(
                'metafad.modules.thesaurus.services.ImportJobService',
                array(
                    'inputFileName' => $inputFileName,
                    'replace' => (__Request::get('Sostituisci_record') !== 'false'),
                    'delAll' => (__Request::get('Cancella_tutti') !== 'false'),
                    'type' => 'GE'
                ),
                'Importazione dizionario '.$dictName,
                'BACKGROUND'
            );

            return array($dictName);
        } else {
            header('HTTP/1.1 412 File non importato');
        }
    }
}
