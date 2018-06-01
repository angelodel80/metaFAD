<?php

/**
 * Class metafad_common_importer_operations_GetXMLNodeList<br>
 * Prende in input un DOMDocument e restituisce una sequenza linearizzata dei nodi del Document senza generare
 * alcun legame di parentela.
 */
class metafad_common_importer_operations_GetXMLNodeList extends metafad_common_importer_operations_LinkedToRunner
{
    protected $rootXPath = null;

    /**
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>rootxpath = XPath(s) per restituire il/i nodo/nodi radice</li>
     * </ul>
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     * @throws Exception se params non è conforme a quanto scritto in questa descrizione
     */
    function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        $this->rootXPath = $params->rootxpath ?: null;

        if (!$this->rootXPath){
            throw new Exception("GetXMLNodeList vuole i parametri passati in una stdClass con all'interno idxpath, rootxpath e childxpath");
        }

        parent::__construct($params, $runnerRef);
    }

    /**
     * @param $xDoc DOMDocument
     * @param $rootXPath string
     * @return array: contiene la lista di DOMElements
     */
    protected function getAllSubNodesBFS($xDoc, $rootXPath){
        $retNodes = array();

        $rootXPath = !is_array($rootXPath) ? array($rootXPath) : $rootXPath;
        $xPath = new DOMXPath($xDoc);

        foreach($rootXPath as $xp){
            foreach($xPath->query($xp) as $node){
                $retNodes[] = $node;
            }
        }

        return $retNodes;
    }

    /**
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>document = Oggetto DOMDocument</li>
     * </ul>
     * Restituisce una stdClass con:<br>
     * <ul>
     * <li>argset = Array di stdClass con all'interno i DOMElement linearizzati (si trovano con la chiave "domElement")</li>
     * </ul>
     *
     * @param stdClass $input
     * @throws Exception se suppressErrors è false (sennò ignora l'eccezione)
     * @return stdClass Array di stdClass con all'interno i DOMElement linearizzati (si trovano con la chiave "domElement")
     */
    function execute($input)
    {
        $arr = $this->getAllSubNodesBFS($input->document, $this->rootXPath);

        $ret = new stdClass();
        $ret->argset = array_map(function($a){$returned = new stdClass(); $returned->domElement = $a; return $returned;}, $arr);

        return $ret;
    }

    function validateInput($input)
    {
        if (!is_a($input->document, "DOMDocument")){
            throw new Exception("Tipo dell'input.document errato, previsto: DOMDocument, ricevuto: " .
                (is_object($input->document) ? get_class($input->document) : gettype($input->document)));
        }
    }
}