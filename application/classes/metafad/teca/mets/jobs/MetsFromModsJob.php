<?php
class metafad_teca_mets_jobs_MetsFromModsJob extends metacms_jobmanager_service_JobService
{
    public function run()
    {
        set_time_limit(0);
        set_error_handler(array($this, 'errorHandler'));
        $param = $this->params;
        metafad_usersAndPermissions_Common::setInstituteKey($param['instituteKey']);
        
        try {
            //$this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);
            
            //Recupero id dei mods da importare
            $ids = explode(',',$param['ids']);
            
            //Inizializzo mapping helper
            $mappingHelper = org_glizy_objectFactory::createObject(
                'metafad.teca.mets.helpers.MappingHelper',
                org_glizy_objectFactory::createObject('metafad.teca.MAG.models.proxy.DocStruProxy')
            );

            //Totale schede MODS
            $total = count($ids);

            //Count
            $count = 0;

            //Non può capitare, ma non si sa mai
            if ($total == 0) {
                $this->setMessage('Attenzione, non ci sono schede importabili per il tipo scelto.');
                $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
                $this->save();
                die;
            } else {
                foreach ($ids as $id) {
                    $mods = __ObjectFactory::createModel('metafad.mods.models.Model');
                    if($mods->load($id))
                    {
                        $mapping = $mappingHelper->getMapping($mods->getRawData());
                        if ($mapping) {
                            $mappingHelper->createMets($mapping, $importMode, $model, $id);
                            $count++;

                            $progress = $count * 100 / $total;
                            $this->updateProgress($progress);
                            $this->save();
                        } else if ($mapping == 'error') {
                            $this->setMessage('Attenzione, non è possibile importare il tipo di scheda selezionato. Servizio non disponibile.');
                            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
                            $this->save();
                            die;
                        }
                    }
                }
            }

            $this->finish();
            $this->setMessage('Creazione METS eseguita. Totale creati: ' . $count);
            $this->save();
        } catch (Error $e) {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
            $this->save();
        }
    }

    public function errorHandler()
    {
        $error = error_get_last();
        if ($error['type'] === E_ERROR) {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
            $this->save();
            die;
        }
    }

}
