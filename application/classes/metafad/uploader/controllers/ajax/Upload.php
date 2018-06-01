<?php
class metafad_uploader_controllers_ajax_Upload extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $decodeData = json_decode($data);
        $files = $decodeData->medias;
        $this->directOutput = true;
        try{
          if ($files->__uploadFilename[0]) {
              $file_path = $files->__uploadFilename[0];
              $file_name = $files->__originalFileName[0];
              $module = $decodeData->module;
              $format = $decodeData->format;
              if($decodeData->format == 'sbn' || $decodeData->format == 'sbnaut')
              {
                $uploadFolder = __Config::get('metafad.'.$decodeData->format.'.uploadFolder.web');
                $outputFolder = __Config::get('metafad.'.$decodeData->format.'.outputFolder.web');
              }
              else
              {
                $uploadFolder = __Config::get('metafad.modules.importer.uploadFolder');
              }
              $zipFile = $uploadFolder.'/'.$file_name;
              $zipFolder = str_replace(".","_",str_ireplace('.zip', '', $zipFile));

              if ($decodeData->format == 'sbn' || $decodeData->format == 'sbnaut') 
              {
                $zipFileOutput = $outputFolder . '/' . $file_name;
                $outputFolder = str_replace(".", "_", str_ireplace('.zip', '', $zipFileOutput));
                if(file_exists($zipFolder))
                {
                  $this->removeDir($zipFolder);
                }
                if(file_exists($outputFolder))
                {
                  $this->removeDir($outputFolder);
                }
              }

              @mkdir($zipFolder, 0777, true);
              if (!@copy($file_path,$uploadFolder.'/'.$file_name)) {
                  throw new Exception('Errore nel caricamento del file:'.$file_name);
                  @unlink($file_path);
              }
              $this->unzip($uploadFolder,str_replace(" ","\ ",$zipFile),$zipFolder);
              $this->removeZip(str_replace(" ","\ ",$zipFile));
              @unlink($file_path);

              //Creazione del job di importazione
              if($decodeData->format == 'sbn' || $decodeData->format == 'sbnaut')
              {
                $jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
                $jobFactory->createJob('metafad.uploader.helpers.Batch',
                                       array(
                                         'format' => $decodeData->format,
                                         'mrcFolder' => $zipFolder
                                        ),
                                       'Preparazione file .MRC per import SBN (file: '.basename($zipFile).')',
                                       'BACKGROUND');
                $url = org_glizy_helpers_Link::makeUrl( 'link', array( 'pageId' => 'metafad.modules.importerreport' ) );
                return array('url' => $url);
              }
              $url = org_glizy_helpers_Link::makeUrl( 'link', array( 'pageId' => 'metafad.uploader' ) );
              return array('url' => $url);
          } else {
            return array('errors'=>array('error' => 'Selezionare un file (o attenderne l\'upload su server).'));
          }
        }
        catch(Error $e){
          return array('errors'=>array('error' => 'Errore: ' . $e->getMessage()));

        }
    }

    private function unzip($uploadFolder,$file,$destination)
    {
      $command = 'cd '.$uploadFolder.'; unzip '.$file.' -d '.str_replace(" ","\ ",$destination);
      $o = org_glizy_helpers_Exec::exec($command);
    }

    private function removeZip($file)
    {
      $command = 'rm -rf '.$file;
      $o = org_glizy_helpers_Exec::exec($command);
    }

    private function removeDir($file)
    {
      $command = 'rm -rf ' . $file;
      $o = org_glizy_helpers_Exec::exec($command);
    }
}
