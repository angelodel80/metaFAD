<?php
set_time_limit( 0 );
class metafad_modules_exporter_services_trcexporter_TRCExporter extends GlizyObject{

    var $dirname;
    var $exportFolder;
    var $TSK;
    var $version;
    var $foldername;
    var $export_path;
    var $autCount;
    var $bibCount;
    var $autESC;
    var $bibESC;
    var $AUTs;
    var $BIBs;
    var $dam;
    var $ESC='';
    var $autbib;

    function getTRC($json, $work)
    {

        foreach ($json as $element) {

            if ($this->isAUT($element))
                $trc .= $this->processAUT($element, $work);
            elseif ($this->isBIB($element))
                $trc .= $this->processBIB($element, $work);
            elseif ($this->isSimpleField($element))
                $trc .= $this->processSimpleField($element, $work);
            elseif ($this->isSimpleContainer($element))
                $trc .= $this->processSimpleContainer($element, $work);
            elseif ($this->isRepeaterContainer($element))
                $trc .= $this->processRepeaterContainer($element, $work);
            elseif ($this->isRepeaterField($element))
                $trc .= $this->processRepeaterField($element, $work);

        }

        return $trc;
    }

    function getElementType($element)
    {
    	if ($this->isSimpleField($element))
    		return "Simple field";
    	elseif ($this->isSimpleContainer($element))
    		return "Simple container";
    	elseif ($this->isRepeaterContainer($element))
    		return "Repeater container";
    	elseif ($this->isRepeaterField($element))
    		return "Repeater field";

    	return "";
    }

    function processSimpleField($element, $work)
    {
        $key = $element['name'];
        $value = $this->valueFor($key, $work);

        if($key=="ESC" && $this->ESC=='') $this->ESC=$value;

        return !empty($value) ? metafad_modules_exporter_services_trcexporter_ICCDTrc::line($key, rtrim($value)) : '';
    }

    function processSimpleContainer($element, $work)
    {
        $key = $element['name'];
        $children = $element['children'];

        if ($this->isObjectNotEmpty($work)) {
            $trc = metafad_modules_exporter_services_trcexporter_ICCDTrc::line($key);
            $trc .= $this->getTRC($children, $this->getWorkElement($key, $work));
        }

        return $trc;
    }

    function processRepeaterContainer($element, $work)
    {
        $key = $element['name'];
        $children = $element['children'];

        if ($this->isObjectNotEmpty($this->getWorkElement($key, $work))) {
            $trc = metafad_modules_exporter_services_trcexporter_ICCDTrc::line($key);
            $c = 0;
            if(is_array($work->$key)){ //MZ aggiunto is_array
              foreach ($work->$key as $r) {
                  if ($c > 0)
                      $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($key);

                  $trc .= $this->getTRC($children, $r);

                  $c++;
              }
            }
        }

        return $trc;
    }

    function processRepeaterField($element, $work)
    {
        $key = $element['name'];

        if ($work->$key != null)
            foreach ($work->$key as $r) {
                $value = $r->{"$key-element"};
                if (!empty($value)) {
                    if (is_object($value))
                        if (property_exists($value, 'text'))
                            $value = $value->text;

                    $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($key, rtrim($value));
                }
            }

        return $trc;
    }

    function processAUT($element, $work)
    {
        $children = $element['children'];

        //if (property_exists($work, 'AUT')) {
        if ((get_class($work)=='stdClass' && property_exists($work, 'AUT')) || $work->fieldExists('AUT')) {
            $auts = $work->AUT;
            if (empty($auts))
                return '';

            foreach ($auts as $aut) {
                $fields = array();
                $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line('AUT');
                //echo"<br> >>>>>";
                foreach ($aut as $key => $value) {
                  //echo"<br>$key";
                    if ($key == '__AUT') {
                        if ($aut->$key->id != NULL) {
                            $moduleName = 'AUT' . $this->version;

                            $w = __ObjectFactory::createModel($moduleName . '.models.Model');
                            $w->load($aut->$key->id);
                            $currentESC = $this->autESC;
                            if (!isset($currentESC) and $w->fieldExists('ESC')) {
                                $escval = $w->ESC;
                                if (isset($escval))
                                    $this->autESC = $w->ESC;
                            }

                            $applicationPath = org_glizy_Paths::get('APPLICATION');
                            $modulePath = $applicationPath . 'classes/userModules/' . $moduleName . '/';

                            if (!in_array($aut->$key->id, $this->AUTs)) {
                                if($this->autbib=="true"){
                                  $exporter = __ObjectFactory::createObject('metafad.modules.exporter.services.trcexporter.TRCExporter');
                                  //$exporter->exportGroup(array($aut->$key->id), $this->foldername, $modulePath, $moduleName, $this->import_path, $this->export_path, $this->autCount, $this->autESC);
                                  $exporter->exportGroup(array($aut->$key->id), $this->foldername, $modulePath, $moduleName, $this->export_path, '', $this->autCount, $this->ESC);
                                }
                                $this->AUTs[] = $aut->$key->id;
                            }

                            $arr = $w->getValuesAsArray();
                            // foreach ($arr as $arrk => $arrv)
                            //     if (preg_match('/^AUT/', $arrk, $matches) and !in_array($arrk, $fields))  {
                            //         if($this->TSK=="F" && !in_array($arrk,array("AUTU","AUTQ","AUTF"))){
                            //           $trc .= ICCDTrc::line($arrk, $arrv);
                            //           $fields[] = $arrk;
                            //         }
                            //     }

                              foreach($element['children'] as $autabil){
                                //echo "<br> >".$autabil['name'];
                                if($arr[$autabil['name']]!=''){
                                  $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($autabil['name'], $arr[$autabil['name']]);
                                  $fields[] = $autabil['name'];
                                  //echo "*";
                                }else{
                                  //echo "**".$aut->{$autabil['name']};

                                  if (strpos($autabil['name'], 'AUT')!== false && $aut->{$autabil['name']}!='') {
                                      if (is_array($aut->{$autabil['name']}) && !in_array($autabil['name'], $fields)) {
                                          foreach ($aut->{$autabil['name']} as $r) {
                                              foreach ($r as $rkey => $rvalue) {
                                                  if (!empty($rvalue)) {
                                                      $k = explode('-', $rkey);
                                                      $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($k[0], $rvalue);
                                                  }
                                              }
                                          }
                                          $fields[] = $autabil['name'];
                                      } elseif (!empty($aut->{$autabil['name']}) and !in_array($autabil['name'], $fields)) {
                                          $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($autabil['name'], $aut->{$autabil['name']});
                                          $fields[] = $autabil['name'];
                                      }
                                  }


                                }
                              }
                        }
                    } elseif (strpos($key, 'AUT') !== false) {
                        if (is_array($value) && !in_array($key, $fields)) {
                            foreach ($value as $r) {
                                foreach ($r as $rkey => $rvalue) {
                                    if (!empty($rvalue)) {
                                        $k = explode('-', $rkey);
                                        $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($k[0], $rvalue);
                                    }
                                }
                            }
                            $fields[] = $key;
                        } elseif (!empty($value) and !in_array($key, $fields)) {
                            $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($key, $value);
                            $fields[] = $key;
                        }
                    }
                }
            }
        }

        return $trc;
    }

    function processBIB($element, $work)
    {
        $children = $element['children'];

        if ((get_class($work)=='stdClass' && property_exists($work, 'BIB')) || $work->fieldExists('BIB')) {
            $bibs = $work->BIB;
            if (empty($bibs))
                return '';

            foreach ($bibs as $bib) {
                $fields = array();
                $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line('BIB');
                //$trc .= ICCDTrc::line('BIBX', $bib->BIBX);
                foreach ($bib as $key => $value) {
                  //if($key != 'BIBX'){
                    if ($key == '__BIB') {
                        if ($bib->$key->id != NULL) {
                            $moduleName = 'BIB' . $this->version;

                            $w = __ObjectFactory::createModel($moduleName . '.models.Model');
                            $w->load($bib->$key->id);
                            $currentESC = $this->bibESC;
                            if (!isset($currentESC) and $w->fieldExists('ESC')) {
                                $escval = $w->ESC;
                                if (isset($escval))
                                    $this->bibESC = $w->ESC;
                            }

                            $applicationPath = org_glizy_Paths::get('APPLICATION');
                            $modulePath = $applicationPath . 'classes/userModules/' . $moduleName . '/';

                            if (!in_array($bib->$key->id, $this->BIBs)) {
                                if($this->autbib=="true"){
                                  $exporter = __ObjectFactory::createObject('metafad.modules.exporter.services.trcexporter.TRCExporter');
                                  //$exporter->exportGroup(array($bib->$key->id), $this->foldername, $modulePath, $moduleName, $this->import_path, $this->export_path, $this->bibCount, $this->bibESC);
                                  $exporter->exportGroup(array($bib->$key->id), $this->foldername, $modulePath, $moduleName, $this->export_path, '', $this->bibCount, $this->ESC);
                                }
                                $this->BIBs[] = $bib->$key->id;
                            }

                            $arr = $w->getValuesAsArray();
                            // foreach ($arr as $arrk => $arrv)
                            //     if (preg_match('/^BIB/', $arrk, $matches) and !in_array($arrk, $fields)) {
                            //         if($this->TSK=="D" && !in_array($arrk,array("BIBZ","BIBL","BIBG","BIBF","BIBC"))){
                            //           $trc .= ICCDTrc::line($arrk, $arrv);
                            //           $fields[] = $arrk;
                            //         }
                            //     }

                              foreach($element['children'] as $bibabil){
                                if($arr[$bibabil['name']]!=''){
                                  $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($bibabil['name'], $arr[$bibabil['name']]);
                                  $fields[] = $bibabil['name'];

                                }else{
                                  //echo "**".$aut->{$autabil['name']};

                                  if (strpos($bibabil['name'], 'BIB')!== false && $bib->{$bibabil['name']}!='') {
                                      if (is_array($bib->{$bibabil['name']}) && !in_array($bibabil['name'], $fields)) {
                                          foreach ($bib->{$bibabil['name']} as $r) {
                                              foreach ($r as $rkey => $rvalue) {
                                                  if (!empty($rvalue)) {
                                                      $k = explode('-', $rkey);
                                                      $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($k[0], $rvalue);
                                                  }
                                              }
                                          }
                                          $fields[] = $bibabil['name'];
                                      } elseif (!empty($bib->{$bibabil['name']}) and !in_array($bibabil['name'], $fields)) {
                                          $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($bibabil['name'], $bib->{$bibabil['name']});
                                          $fields[] = $bibabil['name'];
                                      }
                                  }



                                }
                              }
                        }
                    } elseif (strpos($key, 'BIB') !== false) {
                        if (is_array($value) && !in_array($key, $fields)) {
                            foreach ($value as $r) {
                                foreach ($r as $rkey => $rvalue) {
                                    if (!empty($rvalue)) {
                                        $k = explode('-', $rkey);
                                        $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($k[0], $rvalue);
                                    }
                                }
                            }
                            $fields[] = $key;
                        } elseif (!empty($value) and !in_array($key, $fields)) {
                            $trc .= metafad_modules_exporter_services_trcexporter_ICCDTrc::line($key, $value);
                            $fields[] = $key;
                        }
                    }
                  //}
                }
            }
        }

        return $trc;
    }

    function getCountOf($key, $list)
    {
        $count = 0;

        if (is_array($list) and !empty($list))
            foreach ($list as $el)
                foreach ($el as $k => $v)
                    if ($k === $key)
                        $count++;


        return $count;
    }

    function isObjectNotEmpty($work, $level = 0)
    {
        if (is_a($work, 'org_glizy_dataAccessDoctrine_ActiveRecord'))
            $work = $work->getValuesAsArray();
        elseif (is_object($work))
            $work = (array)$work;

        if (is_array($work)) {
            if (count($work) == 0)
                return false;

            foreach ($work as $k => $v)
                if (is_array($v) or is_object($v)) {
                    $res = $this->isObjectNotEmpty($v, $level + 1);
                    if ($res)
                        return $res;
                } elseif ($work[$k] !== "" and !is_null($work[$k]))
                    return true;
        } elseif ($work !== "" and !is_null($work))
            return true;

        return false;
    }

    function getWorkElement($key, $work)
    {
        if (is_a($work, 'org_glizy_dataAccessDoctrine_ActiveRecord'))
            $res = $work->fieldExists($key) ? $work->$key : $work;
        else
            $res = property_exists($work, $key) ? $work->$key : $work;

        return $res;
    }

    function isAUT($element)
    {
        $name = $element['name'];

        return ($name == 'AUT' and $this->TSK != 'AUT');
    }

    function isBIB($element)
    {
        $name = $element['name'];

        return ($name == 'BIB' and $this->TSK != 'BIB');
    }

    function isSimpleField($element)
    {
        $max = $element['maxOccurs'];
        $children = $element['children'];

        return ($max == '1' and empty($children));
    }

    function isSimpleContainer($element)
    {
        $min = (int)$element['minOccurs'];
        $max = (int)$element['maxOccurs'];
        $children = $element['children'];

        return ($max === 1 and $min === 1 and !empty($children));
    }

    function isRepeaterContainer($element)
    {
        /*$min = (int)$element['minOccurs'];
        $max = $element['maxOccurs'];
        $children = $element['children'];

        return (((int)$max > 1 or $max == 'unbounded') and $min == 0 and !empty($children));*/
    	$max = $element['maxOccurs'];
    	$children = $element['children'];

    	return (((int)$max >= 1 or $max == 'unbounded') and !empty($children));
    }

    function isRepeaterField($element)
    {
        $max = $element['maxOccurs'];
        $children = $element['children'];

        return (((int)$max > 1 or $max == 'unbounded') and empty($children));
    }

    function valueFor($key, $work)
    {
        if (is_a($work, 'org_glizy_dataAccessDoctrine_ActiveRecord'))
            if ($work->fieldExists($key))
                return $work->$key;

        foreach ($work as $k => $v) {
            if ($key === $k)
                return $v;

            if (is_array($v) or is_object($v))
                return $this->valueFor($key, $v);
        }

        return '';
    }

    function isSimpleFieldRepeat($element)
    {
        foreach ($element as $k => $v)
            if (strpos($k, '-element') !== false)
                return true;

        return false;
    }

    function getIMMFTAN($work, &$counter, &$images)
    {
        $c = 0;

        if (!$work->fieldExists('FTA'))
            return '';

        $FTAarr = $work->FTA;
        if (empty($FTAarr))
            return '';

    	foreach ($FTAarr as $fta) {
    	    $c++;

    		$NCTS = $work->NCTS;
    		$RVEL = $work->RV[0]->RVE[0]->RVEL;

            if (array_key_exists('FTA-image', $fta)) {
    		    $img = json_decode($fta->{"FTA-image"});
    		    $imagename = $img->title;
    		    $images[] = array('name' => $imagename, 'id' => $img->id);
    		}

    		$props = array(
    			$counter++,
    			$imagename,
    			'FTAN:' . $fta->FTAN,
    			'NCTR:' . $work->NCTR,
    			'NCTN:' . $work->NCTN,
    			(!empty($NCTS) ? 'NCTS:' . $NCTS : ''),
    			(!empty($RVEL) ? 'RVEL:' . $RVEL : '')
    		);

    		$code .= implode(',', $props) . "\r\n";
    	}

    	return $code;
    }

    function writeExport($trc, $immftan, $images, $dirname, $folderName, $type, $ESC, $workCount = 0)
    {
      $dirname=org_glizy_Paths::get('ROOT').'export/'.$folderName;

        if (!file_exists($dirname))
    	   mkdir($dirname, 0777, true);

    	$filename = "S$ESC$type";

    	if ($type == 'AUT') {
    	   if (!file_exists($dirname . "/A" . $ESC . "AUT"))
    	       $trc = metafad_modules_exporter_services_trcexporter_ICCDTrc::getTRCHeader($this->version, $filename, $workCount, $dirname) . $trc;

    	   file_put_contents($dirname . "/A" . $ESC . "AUT", $trc, FILE_APPEND);
    	} elseif ($type == 'BIB') {
    	   if (!file_exists($dirname . "/A" . $ESC . "BIB"))
    	       $trc = metafad_modules_exporter_services_trcexporter_ICCDTrc::getTRCHeader($this->version, $filename, $workCount, $dirname) . $trc;

    	   file_put_contents($dirname . "/A" . $ESC . "BIB", $trc, FILE_APPEND);
    	} else
    	   file_put_contents($dirname . '/' . $filename, metafad_modules_exporter_services_trcexporter_ICCDTrc::getTRCHeader($this->version, $filename, $workCount, $dirname) . $trc);

    	if (!file_exists($dirname . "/IMMFTAN.txt") and !empty($immftan))
    	   file_put_contents($dirname . "/IMMFTAN.txt", $immftan);

    	if (!empty($images))
            foreach ($images as $img){
                file_put_contents($dirname . '/' . $img['name'], file_get_contents($this->dam->streamUrlLocal( $img['id'], 'original')));
                var_dump($this->dam->streamUrlLocal( $img['id'], 'original'));
            }

      if ($type != 'AUT' && $type != 'BIB')
          $this->zipDir(org_glizy_Paths::get('ROOT').'export/', $folderName);
    }

    function zipDir($baseDir, $folderName)
    {
    	ini_set('memory_limit', '1024M');
    	$currentDir = getcwd();
    	chdir($baseDir);
    	exec("zip -r $folderName.zip $folderName");
    	rrmdir($folderName);
    	chdir($currentDir);
    }

    function countAUT($work, $count = 0)
    {
        foreach ($work as $k => $v) {
            if ('__AUT' === $k and !in_array($v->id, $this->AUTs)) {
                $count++;

                $this->AUTs[] = $v->id;
            }

            if (is_array($v) or is_object($v))
                $count = $this->countAUT($v, $count);
        }

        return $count;
    }

    function countBIB($work, $count = 0)
    {
        foreach ($work as $k => $v) {
            if ('__BIB' === $k and !in_array($v->id, $this->BIBs)) {
                $count++;

                $this->BIBs[] = $v->id;
            }

            if (is_array($v) or is_object($v))
                $count = $this->countBIB($v, $count);
        }

        return $count;
    }

    /* Definizione funzione exportGroup(...)
    *
    * $work_ids: array contenente gli id che fanno riferimento alla tabella documents_tbl
    * $exportFolder: nome della cartella di esportazione
    * $modulePath: percorso del modulo
    * $moduleName: nome del modulo
    * $export_path: percorso alla cartella nella quale verranno esportati i file TRC
    *
    */

    function exportGroup($work_ids, $exportFolder, $modulePath, $moduleName, $export_path, $autbib, $workCount = 0, $ESC = '')
    {

        $this->AUTs = array();
        $this->BIBs = array();

        $dirname = $export_path . $exportFolder;

        $this->foldername = $exportFolder;
        $this->export_path = $export_path;
        $this->dirname = $dirname;
        $this->exportFolder = $exportFolder;
        $this->autbib=$autbib;

        $this->dam = org_glizy_ObjectFactory::createObject("metafad.teca.DAM.services.ImportMedia");

        if (!preg_match('/scheda([a-z]+?)([0-9]{3})/i', $moduleName, $matches))
            preg_match('/([a-z]+?)([0-9]{3})/i', $moduleName, $matches);

        $this->TSK = strtoupper($matches[1]);
        $this->version = $matches[2];

        $moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');
		$elements = $moduleService->getElements($moduleName);
		$elements = json_decode(json_encode($elements), true);

        $counter = 1;
        $images = array();

        $autCount = $this->autCount;
        $bibCount = $this->bibCount;
        if (!isset($autCount) and !isset($bibCount)) {
            $this->autCount = 0;
            $this->bibCount = 0;
            echo "N ".count($work_ids)."<br>";
            foreach ($work_ids as $id) {

                $work = org_glizy_ObjectFactory::createModel('userModules.' . $moduleName . '.models.Model');
                $work->load($id);

                $this->autCount += $this->countAUT($work->getValuesAsArray());
                $this->bibCount += $this->countBIB($work->getValuesAsArray());

                echo "- $id <br>";

            }

            $this->AUTs = array();
            $this->BIBs = array();
        }
        echo "N ".count($work_ids)."<br>";
        foreach ($work_ids as $id) {
            $work = org_glizy_ObjectFactory::createModel('userModules.' . $moduleName . '.models.Model');
            $work->load($id);

            $currentESC = $this->ESC;
            if (empty($currentESC) || $currentESC=="")
                $this->ESC = $work->fieldExists('ESC') ? $work->ESC : 'ESC';

            $trc .= $this->getTRC($json, $work);
            $immftan .= $this->getIMMFTAN($work, $counter, $images);

            echo "- $id <br>";

        }

        $this->writeExport($trc, $immftan, $images, $dirname, $exportFolder, $this->TSK, (!empty($ESC) ? $ESC : $this->ESC), (!empty($workCount) ? $workCount : count($work_ids)));
    	  //$this->writeExport($trc, $immftan, $images, $dirname, $exportFolder, $this->TSK, $this->ESC, (!empty($workCount) ? $workCount : count($work_ids)));
    }
}

function rrmdir($path)
{
    return is_file($path)?
    @unlink($path):
    array_map('rrmdir',glob($path.'/*'))==@rmdir($path);
}

?>
