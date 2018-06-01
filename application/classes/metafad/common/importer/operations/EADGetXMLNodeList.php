<?php

/**
 * Class metafad_common_importer_operations_EADGetXMLNodeList<br>
 * Prende in input un DOMDocument e restituisce una sequenza linearizzata, con approccio BFS, dei nodi del Document.
 */
class metafad_common_importer_operations_EADGetXMLNodeList extends metafad_common_importer_operations_LinkedToRunner
{
    protected $suppressErrors = false;
    protected $childXPath = null;
    protected $idXPath = null;
    protected $rootXPath = null;
    protected $systemAcronym = null;
    protected $dumpDirectory = null;

    /**
     * metafad_common_importer_operations_ReadXML constructor.
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>suppress = Ignora gli errori nella execute (facoltativo)</li>
     * <li>idxpath = XPath per restituire l'identificatore del nodo</li>
     * <li>acronimoSistema = Nome dell'acronimo di sistema</li>
     * <li>rootxpath = XPath per restituire il/i nodo/nodi radice</li>
     * <li>childxpath = XPath per esprimere la ricerca di figli per ogni nodo elaborato</li>
     * <li>dumpdir = directory per il dump</li>
     * </ul>
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     * @throws Exception se params non è conforme a quanto scritto in questa descrizione
     */
    function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        $this->suppressErrors = $params->suppress === true;
        $this->idXPath = $params->idxpath ?: null;
        $this->rootXPath = $params->rootxpath ?: null;
        $this->childXPath = $params->childxpath ?: null;
        $this->childXPath = $params->childxpath ?: null;
        $this->systemAcronym = $params->acronimoSistema ?: null;
        $this->dumpDirectory = $params->dumpdir ?: null;

        if (!$this->idXPath || !$this->rootXPath || !$this->childXPath){
            throw new Exception("EADGetXMLNodeList vuole i parametri passati in una stdClass con all'interno idxpath, rootxpath e childxpath");
        }

        parent::__construct($params, $runnerRef);
    }

    function inferModelFromLivelloDescrizione($livello){
        $descLvlToNumber = $this->getDescrLevels();

        if ($descLvlToNumber['unita'] > $livello){
            return "archivi.models.ComplessoArchivistico";
        } else if ($descLvlToNumber['sottosottounita'] < $livello){
            return "archivi.models.UnitaDocumentaria";
        } else {
            return "archivi.models.UnitaArchivistica";
        }
    }

    /**
     * Modifica del livello di gerarchia secondo le specifiche fornite da Alberto.
     * <p><i>
     *  <ol>
     *   <li>"fonds", "subfile", "file", "item", "series", "subseries" sono i livelli facilmente convertibili.</li>
     *   <li>Sotto a UA, tutto ciò che non è "item", sarà una UA</li>
     *   <li>Se non specificato, il livello sottostante eredita il livello padre</li>
     *   <li>Sopra UA, tutto ciò che non è specificato, sarà una collezione/raccolta</li>
     *  </ol>
     * </i></p>
     *
     * @param $node DOMElement
     * @param $idNode
     * @param $retNodes
     * @param $parentTable
     */
    protected function editHierarchyLevel($node, $idNode, $retNodes, $parentTable)
    {
        //TODO: E se trovo un "file" sotto un "item"...?
        $descLvlToNumber = $this->getDescrLevels();
        $numToDescLvl = $this->invertArray($descLvlToNumber);
        $livello = 2;
        $curLevel = $node->getAttribute("level");
        $curLevel = $node->getAttribute("otherlevel") ?: $curLevel;

        /**
         * @var $parent DOMElement
         */
        $parent = $retNodes[$parentTable[$idNode]];
        if ($parent){
            $parLvlDescr = $parent->getAttribute('metafad_livelloDiDescrizione');
            $livello = $parLvlDescr ? $descLvlToNumber[$parLvlDescr] : $livello;
        }

        if ($curLevel == "file"){
            $livello = $descLvlToNumber['unita'];
        }if ($curLevel == "subfile"){
            $livello = $descLvlToNumber['sottounita'];
        } else if ($curLevel == "item") {
            $livello = $descLvlToNumber['documento-principale'];
        }

        if ($livello >= $descLvlToNumber['unita']){
            $livello = $curLevel == "item" ? $descLvlToNumber['documento-principale'] : $livello;
        } else { //Siamo sopra UA
            if ($curLevel == "series"){
                $livello = $descLvlToNumber['serie'];
            } else if ($curLevel == "subseries"){
                $livello = $descLvlToNumber['sottoserie'];
            } else if ($curLevel == "fonds"){
                $livello = $descLvlToNumber['fondo'];
            } else {
                $livello = $descLvlToNumber['unita']; //C'era collezione-raccolta
            }
        }
        $node->setAttribute('metafad_livelloDiDescrizione', $numToDescLvl[$livello]);
        $node->setAttribute('metafad_model', $this->inferModelFromLivelloDescrizione($livello));

        if ($parent){
            $this->editUpperLevels($parent, $parentTable[$idNode], $retNodes, $parentTable, $livello);
        }
    }

    /**
     * @param $node DOMElement
     * @param $idNode
     * @param $retNodes
     * @param $parentTable
     * @param $livello int
     */
    protected function editUpperLevels($node, $idNode, $retNodes, $parentTable, $livello)
    {
        $descLvlToNumber = $this->getDescrLevels();
        $numToDescLvl = $this->invertArray($descLvlToNumber);

        $parLvlDescr = $node->getAttribute('metafad_livelloDiDescrizione');
        $parLvl = $descLvlToNumber[$parLvlDescr];

        if ($parLvl > $livello){
            $node->setAttribute('metafad_livelloDiDescrizione', $numToDescLvl[$livello]);
            $node->setAttribute('metafad_model', $this->inferModelFromLivelloDescrizione($livello));
            $parent = $retNodes[$parentTable[$idNode]];
            $this->editUpperLevels($parent, $parentTable[$idNode], $retNodes, $parentTable, $livello);
        }
    }

    /**
     *
     * @param $xDoc DOMDocument
     * @param $rootXPath string
     * @param $childXPath string
     * @param $idXPath string
     * @return array: parentTable ha un array associativo che mette in associazione l'id del figlio con quello del padre; nodes contiene la lista di DOMElements
     */
    protected function getAllSubNodesBFS($xDoc, $rootXPath, $childXPath, $idXPath){
        $retNodes = array();
        $parentTable = array();

        $xPath = new DOMXPath($xDoc);

        $fifo = array();
        foreach($xPath->query($rootXPath) as $node){
            $fifo[] = $node;
        }

        $father = null;
        while(count($fifo) > 0){
            $node = array_shift($fifo);
            $idNodes = $xPath->query($idXPath, $node);

            $idNode = null;
            foreach($idNodes as $id){
                $idNode = $id->textContent;
            }
            $father = $idNode;

            foreach ($xPath->query($childXPath, $node) as $child){
                $fifo[] = $child;

                $childIdNodes = $xPath->query($idXPath, $child);
                $childId = null;
                foreach($childIdNodes as $c){
                    $childId = $c->textContent;
                }
                $child->setAttribute("metafad_parentExternalId", $father);
                $parentTable[$childId] = $father;
            }

            $retNodes[$idNode] = $node;
            $this->editHierarchyLevel($node, $idNode, $retNodes, $parentTable);
            $node->setAttribute("metafad_acronimoSistema", $this->systemAcronym ?: "UKWN");
        }

        unset($fifo, $xPath);

        return array("nodes" => $retNodes, "parentTable" => $parentTable);
    }

    /**
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>document = Oggetto DOMDocument</li>
     * </ul>
     * Restituisce una stdClass con:<br>
     * <ul>
     * <li>argset = Array di stdClass con all'interno di essi i DOMElement linearizzati (si trovano con la chiave "domElement")
     * accompagnati da un id univoco che corrisponde all'idxpath delineato (si trovano con la chiave "idDomElement")</li>
     * </ul>
     *
     * Inoltre, scrive il dizionario delle relazioni tra nodi nell'istanza del MainRunner,
     * con chiave "parentship" e inizializza un nuovo dizionario che mette in relazione l'id del nodo con
     * l'id del record salvato nel DB.
     * @param stdClass $input
     * @throws Exception se suppressErrors è false (sennò ignora l'eccezione)
     * @return stdClass Array di stdClass con all'interno i DOMElement linearizzati (si trovano con la chiave "domElement")
     */
    function execute($input)
    {
        $arr = $this->getAllSubNodesBFS($input->document, $this->rootXPath, $this->childXPath, $this->idXPath);

        $ret = new stdClass();
//        $ret->argset = array_map(function($a){$returned = new stdClass(); $returned->domElement = $a; return $returned;}, $arr['nodes']);
        $ret->argset = array();
        foreach($arr['nodes'] as $k => $v){
            $ret->argset[] = (object)array("domElement" => $v, "idDomElement" => $k);
            if ($this->dumpDirectory){
                $this->dumpNode($this->dumpDirectory, $k, $v);
            }
        }

        return $ret;
    }

    private function dumpNode($directory, $id, $node){
        /**
         * @var $dump metafad_common_helpers_DOMPrinter
         */
        $dump = __ObjectFactory::createObject("metafad_common_helpers_DOMPrinter");

        $dump->saveHTML($node, "{$directory}/{$id}.xml", function($node){
            /**
             * @var $node DOMElement
             */
            $xp = new DOMXPath($node->ownerDocument);
            foreach($xp->query("./dsc", $node) as $nodo){
                $node->removeChild($nodo);
            }
            unset($doc, $xp);
        });
    }

    function validateInput($input)
    {
        if (!is_a($input->document, "DOMDocument")){
            throw new Exception("Tipo dell'input.document errato, previsto: DOMDocument, ricevuto: " .
                (is_object($input->document) ? get_class($input->document) : gettype($input->document)));
        }
    }

    function getDescrLevels()
    {
        return array(
            "complesso-di-fondi" => 0,
            "superfondo" => 1,
            "fondo" => 2,
            "sub-fondo" => 3,
            "sezione" => 4,
            "serie" => 5,
            "sottoserie" => 6,
            "sottosottoserie" => 7,
            "collezione-raccolta" => 8,
            "unita" => 9,
            "sottounita" => 10,
            "sottosottounita" => 11,
            "documento-principale" => 12,
            "documento-allegato" => 13
        );
    }

    function invertArray($arr)
    {
        $ret = array();

        foreach ($arr as $k => $v){
            $ret[$v] = $k;
        }

        return $ret;
    }
}