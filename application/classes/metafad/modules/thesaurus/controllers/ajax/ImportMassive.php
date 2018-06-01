<?php

class metafad_modules_thesaurus_controllers_ajax_ImportMassive extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }

        if ($_FILES) {
            ini_set('memory_limit','2048M');
            ini_set('max_execution_time', 0);
            glz_importApplicationLib('PHPExcel/Classes/PHPExcel.php');
            $tmpFile = $_FILES[0]['tmp_name'];

            if (!file_exists($tmpFile)) {
                throw new Exception('Errore nel caricamento del file:' . $_FILES[0]['name']);
            }
            else
            {
              $inputFileName = $tmpFile;
            }

            $dirName = dirname($inputFileName);
            $fileList = array();
            $dictionaryNames = array();
            //Estraggo file da archivio
            $zip = new ZipArchive;
            if ($zip->open($inputFileName) === TRUE) {
              for ($i = 0; $i < $zip->numFiles; $i++) {
                if(substr($zip->getNameIndex($i),-1) !== '/')
                {
                  $fileList[] = $dirName.'/'.$zip->getNameIndex($i);
                }
              }
              $zip->extractTo($dirName);
              $zip->close();
            }

            foreach ($fileList as $key => $inputFileName) {
              try{
                    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                    $objPHPExcel = $objReader->load($inputFileName);
              } catch (Exception $e) {
                  continue;
              }

              $sheet = $objPHPExcel->getSheet();
              $highestRow = $sheet->getHighestRow();
              $highestColumn = $sheet->getHighestColumn();

              $headerRowData = $sheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);

              if ($headerRowData[0][0] != 'Valore' || $headerRowData[0][1] != 'Chiave' || $headerRowData[0][2] != 'Livello' || $headerRowData[0][3] != 'Figlio di') {
                  continue;
              }

              //Cerco dizionario da codice, se c'è già lo prendo, altrimenti lo creo
              $thesaurusName = end(explode("/",$inputFileName));
              $dictionaryNames[] = $thesaurusName;
              $t = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Thesaurus')
                   ->where('thesaurus_code',$thesaurusName)->first();
              if($t)
              {
                $idDizionario = $t->thesaurus_id;
              }
              else
              {
                $t = org_glizy_ObjectFactory::createModel('metafad.modules.thesaurus.models.Thesaurus');
                $t->thesaurus_name = $thesaurusName;
                $t->thesaurus_code = $thesaurusName;
                $t->thesaurus_creationDate = new org_glizy_types_DateTime();
                $t->thesaurus_modificationDate = new org_glizy_types_DateTime();
                $idDizionario = $t->save();
              }

              //Cancello tutto se richiesto
              if(__Request::get('Cancella_tutti') === 'true'){
                  $it = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Details');
                  $it->where('thesaurusdetails_FK_thesaurus_id', $idDizionario);
                  metafad_Metafad::logAction('metafad_modules_thesaurus_controllers_ajax_ImportMassive delete id:'.$idDizionario, 'thesaurus');
                  foreach ($it as $ar) {
                      $ar->delete();
                  }
              }

              //Leggo il file xls e salvo i valori in arrayData
              $arrayData = array();
              $dates = array();
              for ($r = 2; $r <= $highestRow; $r++) {
                $rowData = $sheet->rangeToArray('A' . $r . ':' . $highestColumn . $r, NULL, TRUE, FALSE);
                $row = $rowData[0];
                $arrayData[] = array($row[0],$row[1],$row[2],$row[3]);
              }

              //Ordino i dati estratti sul campo "Livello"
              foreach ($arrayData as $key => $row) {
                  $dates[$key]  = $row[2];
              }
              array_multisort($dates, SORT_ASC, $arrayData);

              //Inizio
              foreach ($arrayData as $key => $row) {
                  $parent = $row[3];

                  $model = org_glizy_ObjectFactory::createModel('metafad.modules.thesaurus.models.Details');
                  $model->thesaurusdetails_FK_thesaurus_id = $idDizionario;
                  $model->thesaurusdetails_value = $row[0];

                  if (__Request::get('Sostituisci_record') === 'true') {
                      $it = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Details');
                      $it->where('thesaurusdetails_key', $row[1]);
                      metafad_Metafad::logAction('metafad_modules_thesaurus_controllers_ajax_ImportMassive delete key:'.$row[1], 'thesaurus');
                      foreach ($it as $ar) {
                          $ar->delete();
                      }
                  }
                  $model->thesaurusdetails_key = $row[1];
                  $model->thesaurusdetails_level = $row[2];

                  if ($parent) {
                      $it = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusDetails');
                      $parentId = null;
                      $ar = $it->where('thesaurusdetails_key', $parent)->where('thesaurus_id', $idDizionario)->first();
                      //Se il padre non viene trovato allora non verrà salvato (in caso di assenza dal file caricato)
                      if($ar)
                      {
                        $parentId = $ar->getRawData()->thesaurusdetails_id;
                        $model->thesaurusdetails_parent = $parentId;
                      }
                  }

                  $model->thesaurusdetails_creationDate = new org_glizy_types_DateTime();
                  $model->thesaurusdetails_modificationDate = new org_glizy_types_DateTime();

                  $model->save();
              }
            }
        } else {
            header('HTTP/1.1 412 File non importato');
        }
        return $dictionaryNames;
    }
}
