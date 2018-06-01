<?php

class metafad_modules_importer_helpers_Batch extends metacms_jobmanager_service_JobService
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

        $data = $evt->data;
        if (key_exists('progress', $data)){
            $this->updateProgress(floor($data['progress']));
        }
        if (key_exists('message', $data)){
            $this->setMessage($data['message']);
        }

//        $progress = round($data['progress'], 2);
//        echo "Progress {$progress}%: {$data['message']}\r\n<br>\r\n";
        $this->save();
    }

    public function run()
    {
        $this->addEventListener('mainRunnerProgress', $this);
        set_error_handler(function($num, $str, $file, $line){
            throw new Error(
                "Errore PHP n°$num: $str\r\n" .
                "$file:$line"
            );
        });

        set_error_handler(array($this, 'errorHandler'));
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $log = org_glizy_log_LogFactory::create('DB', array(), GLZ_LOG_ALL, '*');
        try {
            $param = $this->params;

            $format = $param['format'];
            $arrayAUTBIB = $param['arrayAUTBIB'];
            $arrayForm = $param['arrayForm'];
            $zipFolder = $param['zipFolder'];
            $instance = $param['instance'];

            metafad_usersAndPermissions_Common::setInstituteKey($instance);

            $this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);

            if ($format === 'trc') {
                $this->trcImportProcedure($arrayAUTBIB, $zipFolder, $log, $arrayForm);
            } else if ($format === 'iccdxml') {
                $this->xmlImportProcedure($arrayAUTBIB, $zipFolder, $instance, $log, $arrayForm);
            } else if ($format === 'tei') {

                $importer = __ObjectFactory::createObject('metafad.modules.importer.services.teiimporter.TEIImporter');
                $importer->importTEI($zipFolder,$instance);

            } else {
                $this->setMessage('Il pacchetto non presenta file del formato selezionato (' . $format . ')');
                $this->save();
            }

            @unlink($zipFolder . '.zip');
            org_glizy_helpers_Files::deleteDirectory($zipFolder);
            $this->finish();
            $this->setMessage('Importazione eseguita');
            $this->save();
        } catch (Exception $e) {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
            $message = json_encode(metafad_common_helpers_ImporterCommons::getThrowableString($e), JSON_PRETTY_PRINT);
            $this->setMessage($e->getMessage());
            $log->debug($message);
            $this->save();
        } catch (Error $e) {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
            $message = json_encode(metafad_common_helpers_ImporterCommons::getThrowableString($e), JSON_PRETTY_PRINT);
            $this->setMessage($e->getMessage());
            $log->debug($message);
            $this->save();
        }

    }

    /**
     * @param $zipFolder
     * @param $vector
     * @param $instance
     * @param null $logger
     * @return mixed
     */
    private function getStdClassPipeline($zipFolder, $vector, $instance, $logger = null)
    {
        $pipeline = $this->getImporterInStdClass($zipFolder, $vector, $instance);
        $stdClassesPipeline = json_decode(json_encode($pipeline));
        foreach ($stdClassesPipeline as $k => $v){
            if ($v->obj == "metafad_common_importer_operations_Iterate"){
                if ($v->params->operations){
                    foreach ($v->params->operations as $k1 => $v1){
                        if ($v1->obj == "metafad_common_importer_operations_LogInput"){
                            $v1->params = $v1->params ?: new stdClass();
                            $v1->params->logger = $logger;
                        }
                    }
                }
            }
        }

        return $stdClassesPipeline;
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

    /**
     * @param $zipFolder
     * @param $v
     * @param $instance
     * @return array
     */
    private function getImporterInStdClass($zipFolder, $v, $instance)
    {
        return array(
            array(
                "obj" => "metafad_common_importer_operations_ICCD_TRCFromXML",
                "params" => array("filename" => "$zipFolder/{$v['file']}"),
                "weight" => 110
            ),
            array(
                "obj" => "metafad_common_importer_operations_ICCD_TRCSplitRecords",
                "params" => array("splitKey" => "CD"),
                "weight" => 50
            ),
            array(
                "obj" => "metafad_common_importer_operations_InstituteSetter",
                "params" => array("ignoreInput" => true, "instituteKey" => $instance),
                "weight" => 10
            ),
            array(
                "obj" => "metafad_common_importer_operations_Iterate",
                "weight" => 3500,
                "params" => array(
                    "operations" => array(
                        array(
                            "obj" => "metafad_common_importer_operations_ICCD_TRCToStdClass",
                            "params" => array("type" => $v['type'], "version" => $v['version'], "modulename" => $v['moduleName'])
                        ),
                        array(
                            "obj" => "metafad_common_importer_operations_ICCD_BindMedia",
                            "params" => array(
                                "immftanClass" => "metafad_modules_importer_iccd_services_immftanFromXml",
                                "immftanArg" => "$zipFolder/IMMFTAN.xml"
                            )
                        ),
                        array(
                            "obj" => "metafad_common_importer_operations_ResolveImages",
                            "params" => array("imagesDir" => "$zipFolder/")
                        ),
                        array(
                            "obj" => "metafad_common_importer_operations_ICCD_LinkAutBib",
                            "params" => array(
                                'modelName' => 'AUT300.models.Model',
                                'refsField' => 'AUT',
                                'altRefsField' => 'AU',
                                'refHeader' => 'AUTH',
                                'refNumber' => 'AUTN',
                                'returnSigla' => '__AUT',
                            )
                        ),
                        array(
                            "obj" => "metafad_common_importer_operations_ICCD_LinkAutBib",
                            "params" => array(
                                'modelName' => 'BIB300.models.Model',
                                'refsField' => 'BIB',
                                'altRefsField' => 'DO',
                                'refHeader' => 'BIBH',
                                'refNumber' => 'BIBA',
                                'returnSigla' => '__BIB',
                            )
                        ),
                        array(
                            "obj" => "metafad_common_importer_operations_ICCD_SetRelations",
                            "params" => array("modelName" => $v['modelPath'])
                        ),
                        array(
                            "obj" => "metafad_common_importer_operations_ICCD_SaveData"
                        ),
                        array(
                            "obj" => "metafad_common_importer_operations_LogInput",
                            "params" => array(
                                "instructions" => array (
                                    "message" => "Salvato ID_DB: <##id##>, TSK:<##TSK##>, NCTR-NCTN:<##NCTR##>-<##NCTN##>, OGTD:<##OGTD##>",
                                    "valueSrc" => array (
                                        "id" => "result->set->__id",
                                        "TSK" => "data->TSK",
                                        "NCTN" => "data->NCTN",
                                        "OGTD" => "data->OGTD",
                                        "NCTR" => "data->NCTR"
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            array(
                "obj" => "metafad_common_importer_operations_ICCD_CountSchede",
                "weight" => 10
            )
        );
    }

    /**
     * @param $v
     * @param $zipFolder
     * @param $log
     * @return object
     */
    protected function importTRCFile($v, $zipFolder, $log)
    {
        $fileExtractor = __ObjectFactory::createObject('metafad.modules.importer.iccd.services.TRCFromFile', $v['type'], $v['version'], $v['moduleName'], $zipFolder . '/', $v['file']);
        $imageLinker = __ObjectFactory::createObject('metafad.modules.importer.iccd.services.Immftan', $zipFolder . '/IMMFTAN.txt');
        $proxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy');
        $mediaImporter = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');

        $importerICCD92 = __ObjectFactory::createObject('metafad.modules.importer.iccd.services.ImporterICCD92',
            $fileExtractor,
            $imageLinker,
            $proxy,
            $mediaImporter,
            $v['modelPath'],
            $log,
            $zipFolder . '/'
        );

        $params = $this->params;

        $importerICCD92->import(
            array(
                "overwriteScheda" => key_exists("overwriteScheda", $params) ? $params['overwriteScheda'] : "true",
                "overwriteAuthority" => key_exists("overwriteAuthority", $params) ? $params['overwriteAuthority'] : "true"
            )
        );

        return $importerICCD92;
    }

    /**
     * @param $arrayAUTBIB
     * @param $zipFolder
     * @param $log
     * @param $arrayForm
     */
    protected function trcImportProcedure($arrayAUTBIB, $zipFolder, $log, $arrayForm)
    {
        $this->currentNumber = 0;
        $this->importationsNumber = count($arrayAUTBIB) + count($arrayForm);
        foreach ($arrayAUTBIB as $v) {
            $this->importTRCFile($v, $zipFolder, $log);
            $this->currentNumber++;
        }
        foreach ($arrayForm as $v) {
            $this->importTRCFile($v, $zipFolder, $log);
            $this->currentNumber++;
        }
    }

    /**
     * @param $arrayAUTBIB
     * @param $zipFolder
     * @param $instance
     * @param $log
     * @param $arrayForm
     */
    protected function xmlImportProcedure($arrayAUTBIB, $zipFolder, $instance, $log, $arrayForm)
    {
        $this->currentNumber = 0;
        $this->importationsNumber = count($arrayAUTBIB) + count($arrayForm);

        $runner = __ObjectFactory::createObject("metafad.common.importer.MainRunner");
        foreach ($arrayAUTBIB as $v) {
            $stdClassPipeline = $this->getStdClassPipeline($zipFolder, $v, $instance, $log);
            $runner->executeFromStdClasses($stdClassPipeline);
            $this->currentNumber++;
        }
        foreach ($arrayForm as $v) {
            $stdClassPipeline = $this->getStdClassPipeline($zipFolder, $v, $instance, $log);
            $runner->executeFromStdClasses($stdClassPipeline);
            $this->currentNumber++;
        }

        $this->updateProgress(100);
        $this->setMessage('Importazione da file schede eseguita');
        $this->save();
    }
}
