<?php
ini_set('memory_limit','2048M');
ini_set('max_execution_time', 0);

class archivi_services_ReorderService extends metacms_jobmanager_service_JobService
{
    public function run()
    {
        try {
            $this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);
            $archiviProxy = __ObjectFactory::createModel("archivi.models.proxy.ArchiviProxy");
            $ar = $archiviProxy->getRootAr($this->params['id']);
            $archiviProxy->setOrdinamentoGlobale($ar, 0);
            $this->finish('Task completato');
        } catch (Error $e) {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
        }
    }
}
