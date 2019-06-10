<?php
class metafad_sbn_modules_importer_helpers_Batch extends metacms_jobmanager_service_JobService
{
  public function run()
  {
    set_time_limit(0);
    set_error_handler(array($this, 'errorHandler'));
    $param = $this->params;
    $format = $param['format'];
    $folder = $param['folder'];
    $uploadType = $param['uploadType'];

    try {
      $this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);

      $arrayProfile = array('toSolr','toSolrMetaindice');
      $serviceStatus = __Config::get('metafad.sbn.import.status');
      $sbnHelper = org_glizy_ObjectFactory::createObject('metafad.sbn.modules.importer.helpers.ImportSbnHelper');
      $model = ($format == 'sbn') ? 'metafad.sbn.modules.sbnunimarc.model.Model' : 'metafad.sbn.modules.authoritySBN.model.Model';
      $documentType = ($format == 'sbn') ? 'metafad.chviewer.modules.solr' : 'metafad.gestioneDati.authoritySBN';
      $docType = ($format == 'sbn') ? 'unimarcSBN' : 'authoritySBN' ;
      $folderWeb = __Config::get('metafad.'.$format.'.outputFolder.web').'/'.$folder;
      $folderWebMRC = __Config::get('metafad.'.$format.'.uploadFolder.web').'/'.$folder;
      $folderSolr = __Config::get('metafad.'.$format.'.uploadFolder.solr').'/'.$folder;

      $importJsonResult = $sbnHelper->importJsonToDb($folderWeb,$model,$documentType,$docType,$uploadType);
      $hasMediaIdList = $importJsonResult['hasMediaIdList'];
      $idListNew = $importJsonResult['idListNew'];
      $idListOld = $importJsonResult['idListOld'];

      $progress = 50;
      $this->updateProgress($progress);

      $profile = ($format == 'sbnaut') ? '&profile=au' : '';

      if($uploadType !== 'dbonly')
      {
        foreach ($arrayProfile as $profile) {
          $message = $sbnHelper->importFE($folderSolr,$output,$profile,$folderWebMRC,$uploadType);

          if(!$message)
          {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
            $this->setMessage('Non è stato possibile trovare il file .MRC per eseguire l\'import sugli indici (percorso :'.$folderSolr.')');
            $this->save();
            die;
          }
          $response = $this->checkStatus($message,$serviceStatus);
          if($response->status == 'error')
          {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
            $this->setMessage('Il seguente errore si è verificato in fase di import: ' .$response->message);
            $this->save();
            die;
          }
          $this->updateProgress($progress + 20);
        }
      }

      if($format !== 'sbnaut')
      {
        $sbnHelper->updateSbnDigitale($hasMediaIdList);
        //TODO attivare a regime, momentamente spento, ora non serve
        //$sbnHelper->updateSbnEcommerce($hasMediaIdList);
      }

      //Creazione report
      $list = array();
      foreach($idListNew as $id)
      {
        $list[] = array('id' => $id, 'new' => 1, 'hasMedia' => 0 );
      }
      foreach ($idListOld as $id) 
      {
        $list[] = array('id' => $id, 'new' => 0, 'hasMedia' => ((in_array($id, $hasMediaIdList)) ? 1 : 0));
      }

      $this->setMessage('Creazione file di report in corso...');
      $reportFile = $this->createAndWriteCodesFile($list);

      if(!$statusError)
      {
        $this->finish();
        $this->setMessage('Import pacchetto eseguito. Clicca <a href="'.$reportFile.'" target="_blank" download="report_unimarc.txt">qui</a> per il report.');
        $this->save();
      }
    }
    catch(Error $e){
      $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
      $this->save();
    }
  }

  public function errorHandler(){
    $error = error_get_last();
    if ($error['type'] === E_ERROR) {
      $this->setMessage('Si è verificato un errore in fase di import: '. $error['message']);
      $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
      $this->save();
      die;
    }
  }

  private function checkStatus($message,$serviceStatus) {
    $response = json_decode(file_get_contents($serviceStatus));
    while($response->status != 'finished')
    {
      $this->setMessage('Il servizio di caricamento su indice è in fase : ' .$response->status);
      $this->save();
      $response = json_decode(file_get_contents($serviceStatus));
      if($response->status == 'error')
      {
        return $response;
      }
      sleep(10);
    }
    return $response;
  }

  private function createAndWriteCodesFile($list)
  {
    if (!file_exists('cache')) {
      mkdir('cache', 0777, true);
    }
    $randomString = md5(uniqid(rand(), true));
    $codesFileName = 'cache/unimarc_' . $randomString . '.txt';
    $codesFile = fopen($codesFileName, "w") or die("Unable to open file!");
    if ($codesFileHeader) {
      fwrite($codesFile, 'Report import UNIMARC' . "\r\n\r\n");
    }

    fwrite($codesFile, "BID\tNUOVO\tDIGITALE\r\n");
    foreach($list as $l)
    {
      fwrite($codesFile, implode("\t", $l));
      fwrite($codesFile, "\r\n");
    }

    fclose($codesFile);
    return $codesFileName;
  }
}
