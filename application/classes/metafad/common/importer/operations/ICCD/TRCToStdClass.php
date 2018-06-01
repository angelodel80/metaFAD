<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 28/11/16
 * Time: 12.17
 */
class metafad_common_importer_operations_ICCD_TRCToStdClass extends metafad_common_importer_operations_LinkedToRunner
{
    protected
        $type,
        $version,
        $moduleName,
        $repeatables,
        $withChildren,
        $modulePath,
        $struct;

    /**
     * Si aspetta:
     * type (facoltativo, priorità 1) = tipo di scheda (AUT, BIB esempio)
     * version (facoltativo, priorità 1) = versione del tipo di scheda (300 o 400 esempio)
     * modulename (facoltativo, priorità 2) = nome del modulo, se non specificato concatena i due sopra
     * metafad_common_importer_operations_ICCD_TRCGetDataFromRecords constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     */
    public function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        $this->type = $params->type;
        $this->version = $params->version;
        $this->moduleName = $params->modulename ?: "Scheda{$this->type}{$this->version}";

        $applicationPath = org_glizy_Paths::get('APPLICATION');
        $this->modulePath = $applicationPath . 'classes/userModules/' . $this->moduleName . '/';

        $moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');
        $this->struct = $moduleService->getElements($this->moduleName);

        $this->setRepeatablesAndWithChildren($this->struct);

        parent::__construct($params, $runnerRef);
    }

    protected function setRepeatablesAndWithChildren($arr)
    {
        foreach ($arr as $el) {
            $min = $el['minOccurs'];
            $max = $el['maxOccurs'];
            $children = $el['children'];

            //if ($max == 'unbounded' or ((int)$max > 1))
            if ($max == 'unbounded' or (!empty($children) and $min == 0))
                $this->repeatables[] = $el['name'];

            if (!empty($children)) {
                $this->withChildren[] = $el['name'];
                $this->setRepeatablesAndWithChildren($children);
            }
        }
    }

    /**
     * Riceve in input:
     * data blocco k:v che rappresentano le schede (cioè, array di array di stringhe, un array a scheda)
     * questo blocco è contenuto nella proprietà "data"
     * Restituisce:
     * data = array di dati in formato finale (precedente al salvataggio)
     * @param stdClass $input
     * @return stdClass
     * @throws Exception se il file non esiste
     */
    public function execute($input)
    {
        $result = $input->data;

        $ret = $this->getTrcRecord($result);

        return (object)array("data" => $ret);
    }


    /**
     * Creazione struttura TRC
     *
     * @param array $stringhe contiene una lista di k:v Struttura temporanea creata
     *
     * @return stdClass $record Oggetto stdClass che ricalca la struttura TRC
     */
    protected function getTrcRecord($stringhe)
    {
        $result = $stringhe;
        $record = new stdClass;

        $counter = 0;

        while ($counter < count($result)) {
            $line = trim($result[$counter]);
            if (!empty($line) && preg_match('/^(\w{2,5}):(.*)$/', $line, $matches)) {
                list($key, $value) = $this->getKeyValue($line);
                if (in_array($key, $this->repeatables)) {

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

            $counter++;
        }

        return $record;
    }

    protected function getKeyValue($line)
    {
        if (preg_match('/^(\w{2,5}):(.*)$/', trim($line), $matches)) {
            return array(trim($matches[1]), trim($matches[2]));
        }

        return array();
    }

    protected function getFields($key, $value, $result, $counter)
    {
        //Se $key è ripetibile
        if (in_array($key, $this->repeatables)) {
            $array = array();

            //Se può avere figli
            if (in_array($key, $this->withChildren)) {
                $children = $this->getChildren($key, $this->struct);
                $childNames = $this->getChildNames($children);

                $childValues = new stdClass();

                do {
                    list($k, $v) = $this->getKeyValue($result[++$counter]);

                    $isChild = in_array($k, $childNames);
                    if ($isChild) {
                        list($fields, $counter) = $this->getFields($k, $v, $result, $counter);
                        if (!in_array($k, $this->repeatables) and in_array($k, $this->withChildren)) {
                            foreach ($fields as $field) {
                                if (!empty($field)) {
                                    foreach ($field as $fk => $fv) {
                                        $childValues->$fk = $fv;
                                    }
                                }
                            }
                        } else {
                            $childValues->$k = $fields;
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
        } elseif (in_array($key, $this->withChildren)) {
            $children = $this->getChildren($key, $this->struct);
            $childNames = $this->getChildNames($children);

            do {
                list($k, $v) = $this->getKeyValue($result[++$counter]);

                $isChild = in_array($k, $childNames);
                if ($isChild) {
                    list($fields, $counter) = $this->getFields($k, $v, $result, $counter);
                    $array[$k] = $fields;
                }
            } while ($isChild);

            return array(array($array), $counter - 1);
        } else
            return array($value, $counter);
    }

    protected function getChildren($key, $arr)
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

    protected function getChildNames($children)
    {
        $arr = array();
        foreach ($children as $c)
            $arr[] = $c['name'];

        return $arr;
    }

    public function validateInput($input)
    {
        // TODO: Change the autogenerated stub
    }
}