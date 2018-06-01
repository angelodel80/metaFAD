<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 28/11/16
 * Time: 12.17
 */
class metafad_common_importer_operations_ICCD_TRCFromFile extends metafad_common_importer_operations_LinkedToRunner
{
    protected $file = "";

    /**
     * Si aspetta:
     * filename = nome del file da cui leggere i record.
     * metafad_common_importer_operations_ICCD_TRCFromFile constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     */
    public function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        $this->file = $params->filename;
        parent::__construct($params, $runnerRef);
    }

    /**
     * Restituisce:
     * trcrecords = record in formato trc, ancora da convertire in stdClass
     * @param stdClass $input
     * @return stdClass (trcrecords contiene i record in formato trc)
     * @throws Exception se il file non esiste
     */
    public function execute($input)
    {
        $file = $this->file;
        if (file_exists($file)) {

            list($content, $result) = $this->getRightTrc($file);
            $result = explode("\n", $result);
        } else {
            throw new Exception("File '$file' doesn't exist");
        }

        return (object)array("trcrecords" => $result);
    }

    public function validateInput($input)
    {
        //TODO non controllo nulla
    }

    private function getRightTrc($f)
    {
        $file = (!is_array($f) ? file($f) : $f);

        $content = array();
        $result = "";

        $nl = "\r\n";

        $c = 0;

        for ($i = 0; $i < count($file); $i++) {
            if (preg_match('/^[A-Z]{2,5}:/', $file[$i]) > 0) {
                if (strpos($content[$c - 1], $nl) === false) {
                    $content[$c - 1] .= $nl;
                }

                $content[$c] = $file[$i];

                $c++;
            } elseif (trim($file[$i]) != "") {
                $content[$c - 1] = str_replace(array($nl, "\n"), array("", "", ""), $content[$c - 1] . substr($file[$i], 6));
                $c++;
            }
        }


        for ($i = 0; $i < count($content); $i++)
            $result .= $content[$i];

        $content[$c] = "\nCD:";

        return array($content, str_replace("\n\r", "", $result));
    }
}