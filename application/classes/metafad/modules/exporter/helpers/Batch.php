<?php
class metafad_modules_exporter_helpers_Batch extends metacms_jobmanager_service_JobService
{
  public function run()
  {
    set_error_handler(array($this, 'errorHandler'));

    try{

      $this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);

      $param = $this->params;

      $format = $param['format'];
      $arrayIds = $param['arrayIds'];
      $moduleName = $param['moduleName'];
      $autbib = $param['autbib'];
      $folderName = $param['foldername'];
      $damInstance = $param['damInstance'];
      $email = $param['email'];
      $title = $param['title'];
      $host = $param['host'];

      metafad_usersAndPermissions_Common::setInstituteKey($damInstance);

      if (!preg_match('/scheda([a-z]+?)([0-9]{3})/i', $moduleName, $matches))
          preg_match('/([a-z]+?)([0-9]{3})/i', $moduleName, $matches);

      $TSK = strtoupper($matches[1]);
      $version = $matches[2];

      $moduleName="Scheda".$TSK.$version;

      $applicationPath = org_glizy_Paths::get('APPLICATION');
      //$export_path = $applicationPath . 'mediaArchive_iccd/exportICCD/';
      $export_path = org_glizy_Paths::get('ROOT').'export/';

      $modulePath = $applicationPath . 'classes/userModules/' . $moduleName . '/';

      if($format=="trc"){

        $exporter = __ObjectFactory::createObject('metafad.modules.exporter.services.trcexporter.TRCExporter');
        $exporter->exportGroup($arrayIds, $folderName, $modulePath, $moduleName, $export_path, $autbib);

      }else if ($format=="iccdxml"){

        $exporter = __ObjectFactory::createObject('metafad.modules.exporter.services.xmlexporter.TrcExporterXML');
        $exporter->exportGroup($arrayIds, $modulePath, $moduleName, $export_path, $folderName, $autbib);

      }else if ($format=="mets"){

        $exporter = __ObjectFactory::createObject('metafad.modules.exporter.services.metsexporter.METSExporter');
        $exporter->METSExport($arrayIds, $damInstance, $folderName);

      }else{
        $this->setMessage('Il job non è compatibile con il formato selezionato ('.$format.')');
        $this->save();
      }

      $this->updateProgress(100);
      $this->setMessage('Esportazione schede eseguita');
      $this->updateStatus(metacms_jobmanager_JobStatus::COMPLETED);
      $this->save();
      if($email!='') metafad_modules_iccd_helpers_ExportHelper::sendDownloadEmail($email,$title,new org_glizy_types_DateTime,$host,$format,$folderName);

    }
    catch(Error $e){
        $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
        $this->save();
    }

  }

  public function errorHandler(){
      $error = error_get_last();
      if ($error['type'] === E_ERROR) {
          $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
          $this->save();
          die;
      }

  }
}
