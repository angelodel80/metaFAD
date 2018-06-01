<?php

/**
 * Class metafad_modules_importer_helpers_EACEADBatch
 */
class metafad_modules_importer_helpers_EACEADBatch extends metafad_modules_importer_helpers_AbstractBatch
{
    protected $importationsNumber = 0;
    protected $currentNumber = 0;

    /**
     * Nota bene: progress è un numero da 0 a 100
     * @param $evt
     */
    public function mainRunnerProgress($evt)
    {
        if (property_exists($evt, "data") && key_exists("progress", $evt->data)){
            $p = $evt->data['progress'];
            $t = $this->importationsNumber;
            $c = $this->currentNumber;
            $evt->data['progress'] = ($t ? ($c*100+$p) / $t : 100);
        }

        parent::mainRunnerProgress($evt);
    }

    public function run()
    {
        parent::run();

        $log = org_glizy_log_LogFactory::create('DB', array(), 255, '*');
        $this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);
        try {
            $param = $this->params;

            $zipFolder = $param['zipFolder'];
            $instance = $param['instance'];

            metafad_usersAndPermissions_Common::setInstituteKey($instance);

            $files = $this->rearrangeFiles($zipFolder);
            $this->setMessage("Lettura ZIP completata");
            $this->updateProgress(1);
            array_map(function ($a) use ($log) {
                $log->debug("File '$a' ignorato: formato non riconosciuto");
            }, $files['rejected']);

            $this->currentNumber = 0;
            $this->importationsNumber = count($files['eac']) + count($files['ead']);
            array_map(function ($a) use ($instance, $log, &$progress) {
                metafad_modules_importer_xmlArchiveEADEAC_services_Importers::importEAC($a, $instance, null, $log);
                $this->currentNumber++;
            }, array_merge($files['eac'], $files['ead']));

            @unlink($zipFolder . '.zip');
            org_glizy_helpers_Files::deleteDirectory($zipFolder);
            $this->finish();
            $this->setMessage('Importazione eseguita');
            $this->save();
        } catch (Exception $e) {
            $this->logThrowable($e, $log);
        } catch (Error $e) {
            $this->logThrowable($e, $log);
        }

    }

    private function rearrangeFiles($zipFolder)
    {
        $eac = array();
        $ead = array();
        $rejected = array();

        $files = glob("$zipFolder/*");

        foreach ($files as $file) {
            $xml = new DOMDocument();
            try {
                $xml->loadXML(file_get_contents($file));
                $xp = new DOMXPath($xml);

                if ($xp->query("//rsp/dsc/c")->length > 0) {
                    $ead[] = $file;
                } else if ($xp->query("//xw_doc/eac-cpf")->length > 0) {
                    $eac[] = $file;
                } else {
                    $rejected[] = $file;
                }
            } catch (Exception $ex) {
                $rejected[] = $file;
            }
        }

        return array(
            "eac" => $eac,
            "ead" => $ead,
            "rejected" => $rejected
        );
    }

    /**
     * @param $e Throwable
     * @param $log org_glizy_log_LogBase
     */
    private function logThrowable($e, $log)
    {
        $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
        $message = json_encode(metafad_common_helpers_ImporterCommons::getThrowableString($e), JSON_PRETTY_PRINT);
        $this->setMessage($e->getMessage());
        $log->debug($message);
        $this->save();
    }
}
