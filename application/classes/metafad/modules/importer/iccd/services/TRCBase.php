<?php
class metafad_modules_importer_iccd_services_TRCBase implements metafad_modules_importer_iccd_services_TRCInterface
{

  var $type;
  var $version;
  var $moduleName;
  var $modulePath;
  var $struct;
  var $repeatables;
  var $withChildren;

  public $records;


    public function __construct($type, $version, $moduleName)
    {
        $this->type = $type;
        $this->version = $version;
        $this->moduleName = $moduleName;

        $this->repeatables = array();
        $this->withChildren = array();

        $applicationPath = org_glizy_Paths::get('APPLICATION');
        $this->modulePath = $applicationPath . 'classes/userModules/' . $moduleName . '/';

        //Carico l'array che contiene la struttura completa della scheda senza valori
        $this->setStructureFromModule();
        $this->setRepeatablesAndWithChildren($this->struct);

    }

    /**
    * Creazione struttura TRC
    *
    * @param array $result Struttura temporanea creata
    *
    * @return array $records Oggetto contenente la struttra TRC
    */
    public function getTrcRecords($result)
    {
        $records = array();

        $counter = 0;

        while ($counter < count($result)) {
            $line = trim($result[$counter]);
            if (!empty($line)) {
                if (preg_match('/^(\w{2,5}):(.*)$/', $line, $matches)) {
                    list($key, $value) = $this->getKeyValue($line);

                    if ($key == 'CD') {
                        if (!empty($record))
                            $records[] = $record;

                        $record = new stdClass;
                    } else {
                        if ($this->repeatables[$key]) {
                            list($fields, $c) = $this->getFields($key, $value, $result, $counter);
                            if (property_exists($record, $key))
                                $record->{$key}[] = $fields[0];
                            else
                                $record->{$key} = $fields;

                            $counter = $c;

                        } elseif (!empty($value)) {
                            $record->{$key} = $value;
                        }
                    }
                }
            }

            $counter++;
        }
        $recordarr = (array)$record;
        if (!empty($recordarr)) {
            $records[] = $record;
        }

        return metafad_common_helpers_ImporterCommons::purgeEmpties($records);
    }


    private function getFields($key, $value, $result, $counter)
    {
        //Se $key è ripetibile
        if ($this->repeatables[$key]) {
            $array = array();

            //Se può avere figli
            if ($this->withChildren[$key]) {
                $children = $this->getChildren($key, $this->struct);
                $childNames = $this->getChildNames($children);

                $childValues = new StdClass;

                do {
                    list($k, $v) = $this->getKeyValue($result[++$counter]);

                    $isChild = $childNames[$k];

                    if ($isChild) {
                        list($fields, $counter) = $this->getFields($k, $v, $result, $counter);
                        if (!$this->repeatables[$k] and $this->withChildren[$k]) {
                            foreach ($fields as $field) {
                                if (!empty($field)) {
                                    foreach ($field as $fk => $fv) {
                                        $childValues->$fk = $fv;
                                    }
                                }
                            }
                        } else {
                            if ($childValues->$k) {
                                $childValues->{$k}[] = $fields[0];
                            } else {
                                $childValues->$k = $fields;
                            }
                        }
                    }
                } while ($isChild);

                $array[] = $childValues;

                return array($array, $counter - 1);
            //Se non può avere figli
            } else {
                $obj = new stdClass;
                $obj->{"$key-element"} = $value;

                $array[] = $obj;

                list($k, $v) = $this->getKeyValue($result[++$counter]);

                while ($k == $key) {
                    $obj = new stdClass;
                    $obj->{"$k-element"} = $v;

                    $array[] = $obj;

                    list($k, $v) = $this->getKeyValue($result[++$counter]);
                }

                return array($array, $counter - 1);
            }
        //Se non è ripetibile e può avere figli
        } elseif ($this->withChildren[$key]) {
            $children = $this->getChildren($key, $this->struct);
            $childNames = $this->getChildNames($children);

            do {
                list($k, $v) = $this->getKeyValue($result[++$counter]);

                $isChild = $childNames[$k];
                if ($isChild) {
                    list($fields, $counter) = $this->getFields($k, $v, $result, $counter);
                    $array[$k] = $fields;
                }
            } while ($isChild);

            return array(array($array), $counter - 1);
        } else
            return array($value, $counter);

        return array(null, $counter);
    }


    private function getKeyValue($line)
    {
        if (preg_match('/^(\w{2,5}):(.*)$/', trim($line), $matches)) {
            return array(trim($matches[1]), trim($matches[2]));
        }

        return array();
    }


    private function getChildNames($children)
    {
        $arr = array();
        foreach ($children as $c) {
            $arr[$c['name']] = true;
        }

        return $arr;
    }


    //Importatore da DB: ottengo le schede in un formato simile al TRC
    public function getSimilarTrcStruct($record, $elementStruct, &$trc = array(), $isRepeatable = false, $parent = '')
    {
        if ($isRepeatable and !empty($parent)) {
          if(is_array($record)){ //MZ altrimenti da errore in caso di stringa ripetibile
            foreach ($record as $r) {
                $trc[] = "$parent:";

                foreach ($elementStruct as $element) {
                    $name = $element['name'];
                    $children = $element['children'];
                    $value = array_key_exists($name, $r) ? $r[$name] : '';

          					if (!is_array($value))
          						$value = trim($value);

                    $isRepeatable = $this->repeatables[$name];
                    $keyExists = array_key_exists($name, $r);

                    if (!empty($children)) {
                        if ($isRepeatable and $keyExists) {
                            $this->getSimilarTrcStruct($record[$name], $children, $trc, true, $name);
                        } elseif (!$isRepeatable and $keyExists) {
                            $trc[] = "$name:";
                            $this->getSimilarTrcStruct($record[$name], $children, $trc);
                        } elseif (!$keyExists) {
                            $trc[] = "$name:";
                            $this->getSimilarTrcStruct($r, $children, $trc);
                        }
                    } elseif ($isRepeatable and $keyExists) {
                        if(is_array($r[$name])){
                          foreach ($r[$name] as $key => $value) {
                              $trc[] = "$key:$value";
                          }
                        }else{
                          $trc[] = "$name:$r[$name]"; //MZ forse va tolto l'else... se non è un array non va scritto niente?
                        }
                    } elseif (isset($value) and $value != '')
                        $trc[] = "$name:$value";
                }
            }
          }
        } else {
          if(is_array($record)){ //MZ altrimenti da errore in caso di stringa ripetibile
            foreach ($elementStruct as $element) {
                $name = $element['name'];
                $children = $element['children'];
                $value = array_key_exists($name, $record) ? $record[$name] : '';

        				if (!is_array($value))
        					$value = trim($value);

                $isRepeatable = $this->repeatables[$name];
                $keyExists = array_key_exists($name, $record);

                if (!empty($children)) {
                    if ($isRepeatable and $keyExists) {
                        $this->getSimilarTrcStruct($record[$name], $children, $trc, true, $name);
                    } elseif (!$isRepeatable and $keyExists) {
                        $trc[] = "$name:";
                        $this->getSimilarTrcStruct($record[$name], $children, $trc);
                    } elseif (!$keyExists) {
                        $trc[] = "$name:";
                        $this->getSimilarTrcStruct($record, $children, $trc);
                    }
                } elseif ($isRepeatable and $keyExists) {
                    if(is_array($record[$name])){
                      foreach ($record[$name] as $key => $value) {
                          $trc[] = "$key:$value";
                      }
                    }else{
                      $trc[] = "$name:$record[$name]"; //MZ forse va tolto l'else... se non è un array non va scritto niente
                    }
                } elseif (isset($value) and $value != '')
                    $trc[] = "$name:$value";
            }
          }
        }

        return $trc;
    }


    private function getChildren($key, $arr)
    {
        foreach ($arr as $el) {
            if ($el['name'] == $key)
                return $el['children'];
            elseif (array_key_exists('children', $el)) {
                $children = $this->getChildren($key, $el['children']);
                if ($children != NULL)
                    return $children;
            }
        }

        return NULL;
    }


    private function setStructureFromModule()
    {
        $moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');
		$elements = $moduleService->getElements($this->moduleName);
		$this->struct = json_decode(json_encode($elements), true);
    }


    public function setRepeatablesAndWithChildren($arr)
    {
        foreach ($arr as $el) {
            $min = $el['minOccurs'];
            $max = $el['maxOccurs'];
            $children = $el['children'];

            //if ($max == 'unbounded' or ((int)$max > 1))
            if ($max == 'unbounded' or (!empty($children) and $min == 0))
                $this->repeatables[$el['name']] = true;

            if (!empty($children)) {
                $this->withChildren[$el['name']] = true;
                $this->setRepeatablesAndWithChildren($children);
            }
        }
    }

    //Importatore da DB: funzione per il log a video
    public function slog($msg)
    {
        echo "$msg".PHP_EOL;
        flush();
    }

}
