<?php
ini_set('memory_limit','2048M');
ini_set('max_execution_time', 0);

class metafad_modules_thesaurus_services_ImportJobService extends metacms_jobmanager_service_JobService
{
    protected $alreadyLoaded = false;

    public function run()
    {
        try {
            $this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);
            $importService = __ObjectFactory::createObject('metafad.modules.thesaurus.services.ImportService');
            $importService->importDictionaryZIP($this->params['inputFileName'], $this->params['delAll'], $this->params['replace'], $this->params['type']);
            $this->finish('Esportazione schede eseguita');
        } catch (Error $e) {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
        }
    }
}
