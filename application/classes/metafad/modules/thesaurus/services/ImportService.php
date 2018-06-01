<?php
ini_set('memory_limit','2048M');
ini_set('max_execution_time', 0);

class metafad_modules_thesaurus_services_ImportService extends GlizyObject
{
    protected $alreadyLoaded = false;

    public function importDictionaryZIP($inputZIP, $deleteAll = false, $replaceAll = true, $mode = 'AUTO', $unlinkFiles = true)
    {
        $dirName = dirname($inputZIP);
        $fileList = array();
        $dictionaryNames = array();
        //Estraggo file da archivio
        $zip = new ZipArchive;
        if ($zip->open($inputZIP) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (substr($zip->getNameIndex($i), -1) !== '/' &&
                    strpos($zip->getNameIndex($i), '__MACOSX') === false &&
                    in_array(pathinfo($zip->getNameIndex($i), PATHINFO_EXTENSION), array('csv', 'xls', 'xlsx'))
                ) {
                    $fileList[] = $dirName . '/' . $zip->getNameIndex($i);
                }
            }
            $zip->extractTo($dirName);
            $zip->close();
        }

        foreach ($fileList as $i => $inputFileName) {
            try {
                $dictionaryNames[] = $this->importDictionaryFile($inputFileName, $deleteAll, $replaceAll, $mode);
            } catch (Exception $ex) {
            }

            if ($unlinkFiles) {
                unlink($inputFileName);
            }
        }

        return $dictionaryNames;
    }

    public function importDictionaryFile($inputFileName, $deleteAll = false, $replaceAll = true, $mode = 'AUTO')
    {
        if (!$this->alreadyLoaded) {
            glz_importApplicationLib('PHPExcel/Classes/PHPExcel.php');
            $this->alreadyLoaded = true;
        }

        try {
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            if ($inputFileType === 'CSV') {
                $objReader->setDelimiter(";");
            }
            $objPHPExcel = $objReader->load($inputFileName);
        } catch (Exception $ex) {
            throw new Exception("File $inputFileName was not loaded: {$ex->getMessage()}", 0, $ex);
        }

        if ($mode === 'AUTO') {
            $line = fgets(fopen($inputFileName, 'r'));
            $mode = (strpos($line, 'VISUALIZZAZIONE ALFABETICA') === 0) ? 'AL' : 'GE';
        }

        list($dictionaryName, $dictionaryId, $sheet) = $this->readDictionary($deleteAll, $objPHPExcel);

        if ($mode !== "AL") {
            $this->importGE($dictionaryId, $replaceAll, $sheet);
        } else {
            $this->importAL($dictionaryId, $replaceAll, $sheet);
        }

        return $dictionaryName;
    }

    public function importGE($dictionaryId, $replaceAll, $sheet)
    {
        //Leggo il file e salvo i valori in arrayData
        $arrayData = array();
        $parents = array();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($r = 7; $r <= $highestRow; $r++) {
            $rowData = $sheet->rangeToArray('A' . $r . ':' . $highestColumn . $r, NULL, TRUE, FALSE);
            $row = $rowData[0];

            $i = 4;
            while ($i >= 0) {
                if ($row[$i] !== null) {
                    if ($row[9] !== null) {
                        //Prendo il valore di note d'ambito se esiste
                        $value = $row[9];
                    } else {
                        //Altrimenti il valore sarà uguale alla chiave
                        $value = $row[$i];
                    }
                    $key = (string)$row[$i];
                    if ($i >= 1) {
                        $parent = $row[$i - 1];
                    } else {
                        $parent = null;
                    }
                    $arrayData[] = array($value, $key, $i + 1, $parent);
                    break;
                }
                $i--;
            }
        }

        $this->saveTerms($dictionaryId, $replaceAll, $arrayData);
    }

    /**
     * @param $deleteAll
     * @param $replaceAll
     * @param $objPHPExcel
     * @return mixed
     */
    public function importAL($dictionaryId, $replaceAll, $sheet)
    {
        //Leggo il file e salvo i valori in arrayData
        $arrayData = array();
        $parents = array();
        $dates = array();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($r = 7; $r <= $highestRow; $r++) {
            $rowData = $sheet->rangeToArray('A' . $r . ':' . $highestColumn . $r, NULL, TRUE, FALSE);
            $row = $rowData[0];
            $arrayData[] = array($row[0], $row[0], $row[1], $row[2]);
        }

        //Ordino array sui livelli
        foreach ($arrayData as $key => $row) {
            $dates[$key] = $row[2];
        }

        array_multisort($dates, SORT_ASC, $arrayData);

        $this->saveTerms($dictionaryId, $replaceAll, $arrayData);
    }

    protected function readDictionary($deleteAll, $objPHPExcel)
    {
        $sheet = $objPHPExcel->getSheet();
        $highestColumn = $sheet->getHighestColumn();

        $nameRow = $sheet->rangeToArray('A4:' . $highestColumn . 4, NULL, TRUE, FALSE);
        //Cerco dizionario da codice, se c'è già lo prendo, altrimenti lo creo
        $thesaurusName = $nameRow[0][0];
        $dictionaryName = $thesaurusName;
        $t = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Thesaurus')
            ->where('thesaurus_code', $thesaurusName)->first();

        if ($t) {
            $dictionaryId = $t->thesaurus_id;
        } else {
            $t = org_glizy_ObjectFactory::createModel('metafad.modules.thesaurus.models.Thesaurus');
            $t->thesaurus_name = $thesaurusName;
            $t->thesaurus_code = $thesaurusName;
            $t->thesaurus_creationDate = new org_glizy_types_DateTime();
            $t->thesaurus_modificationDate = new org_glizy_types_DateTime();
            $dictionaryId = $t->save();
        }

        //Cancello tutto se richiesto
        if ($deleteAll) {
            $it = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Details')
                ->load('deleteTerms', array(':dictionaryId' => $dictionaryId))->exec();
        }

        return array($dictionaryName, $dictionaryId, $sheet);
    }

    protected function saveTerms($dictionaryId, $replaceAll, $arrayData)
    {
        $parents = array();

        foreach ($arrayData as $key => $row) {
            if (substr($row[0], 0, 1) !== '(') {
                $parent = $row[3];
                $model = org_glizy_ObjectFactory::createModel('metafad.modules.thesaurus.models.Details');
                $model->thesaurusdetails_FK_thesaurus_id = $dictionaryId;
                $model->thesaurusdetails_value = $row[0];

                if ($replaceAll !== false) {
                    $it = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Details')
                        ->load('deleteTermsByKey', array(':id' => $dictionaryId, ':key' => $row[1]))->exec();
                }

                $model->thesaurusdetails_key = $row[1];
                $model->thesaurusdetails_level = $row[2];

                if ($parent) {
                    if (array_key_exists((string)$parent, $parents)) {
                        $model->thesaurusdetails_parent = $parents[$parent];
                    } else {
                        $it = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusDetails');
                        $parentId = null;
                        $ar = $it->where('thesaurusdetails_key', $parent)->where('thesaurus_id', $dictionaryId)->first();
                        //Se il padre non viene trovato allora non verrà salvato (in caso di assenza dal file caricato)
                        if ($ar) {
                            $parentId = $ar->getRawData()->thesaurusdetails_id;
                            $model->thesaurusdetails_parent = $parentId;
                        }
                        $parents[(string)$parent] = $parentId;
                    }
                }

                $model->thesaurusdetails_creationDate = new org_glizy_types_DateTime();
                $model->thesaurusdetails_modificationDate = new org_glizy_types_DateTime();
                $model->save();
            }
        }
    }
}
