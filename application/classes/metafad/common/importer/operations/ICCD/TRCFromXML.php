<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 28/11/16
 * Time: 12.17
 */
class metafad_common_importer_operations_ICCD_TRCFromXML extends metafad_common_importer_operations_LinkedToRunner
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

            $result = $this->getRightTrc($file);
        } else {
            throw new Exception("File '$file' doesn't exist");
        }

        return (object)array("trcrecords" => $result);
    }

    public function validateInput($input)
    {
        //TODO non controllo nulla
    }

    /**
     * @param $array
     * @param $node DOMElement
     * @param $xp DOMXPath
     */
    private function extractNode(&$array, $node, $xp){
        $count = 0;
        $idx = count($array);
        $array[] = "";

        foreach($xp->query("./*", $node) as $child){
            $count++;
            $this->extractNode($array, $child, $xp);
        }

        $out = $node->tagName . ":";
        if (!$count){
            $out .= " ". $node->nodeValue;
        }

        $array[$idx] = $out;
    }

    private function getRightTrc($f)
    {
        $doc = new DOMDocument();
        $doc->load($f);
        $doc->preserveWhiteSpace = false;

        $xp = new DOMXPath($doc);
        $out = array();
        /**
         * @var $node DOMElement
         */
        foreach($xp->query("/csm_root/schede/scheda") as $node){
            foreach($xp->query("./*", $node) as $child){
                $this->extractNode($out, $child, $xp);
            }
        }

        return $out;
    }
}