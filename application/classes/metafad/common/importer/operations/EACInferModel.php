<?php

/**
 * Class metafad_common_importer_operations_EACInferModel
 * La classe inferisce il modello da mappare a seconda del primo xpath che restituisce un risultato non vuoto.
 */
class metafad_common_importer_operations_EACInferModel extends metafad_common_importer_operations_LinkedToRunner
{
    protected $suppressErrors = false;
    protected $document = null;
    protected $mapping = null;
    /**
     * @var $xpath DOMXPath
     */
    protected $xpath = null;

    /**
     * metafad_common_importer_operations_ReadXML constructor.
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>suppress = Ignora gli errori nella execute (facoltativo)</li>
     * <li>xpathToMappingFile = Pattern matching dei nodi per inferire il modello.
     * La mappa è definita semplicemente come un insieme k:v con k = xpath e v = filename</li>
     * </ul>
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     * @throws Exception se params non è conforme a quanto scritto in questa descrizione
     */
    function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        $this->suppressErrors = $params->suppress === "true";
        $this->mapping = $params->xpathToMappingFile;

        parent::__construct($params, $runnerRef);
    }

    /**
     * WARNING: il mapping restituito è quello del primo xpath che restituisce un risultato non vuoto.
     * Se nessun risultato è corretto, allora il nome del file del mapping inferito è NULL.
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>domElement = Oggetto DOMElement da cui inferire il modello</li>
     * </ul>
     * Restituisce una stdClass con:<br>
     * <ul>
     * <li>domElement = Oggetto DOMElement con il modello inferito</li>
     * <li>schemafile = Nome del file di mapping inferito</li>
     * </ul>
     *
     * @param stdClass $input
     * @throws Exception se suppressErrors è false (sennò ignora l'eccezione)
     * @return stdClass una stdClass con dentro "domElement" (nodo xml interrogabile con xpath) e "schemafile" (il filename
     * del mapping da applicare al nodo con la XmlToJson)
     */
    function execute($input)
    {
        /**
         * @var $node DOMElement
         */
        $node = $input->domElement;

        $this->document = new DOMDocument();
        $this->document->appendChild($this->document->importNode($node, true));
        $this->xpath = new DOMXPath($this->document);

        $input->schemafile = null;
        foreach ($this->mapping as $k => $v) {
            $hit = $this->xpath->query($k);
            $input->schemafile = !isset($input->schemafile) && $hit && $hit->length ? $v : $input->schemafile;
        }

        return $input;
    }

    function validateInput($input)
    {
        if (!is_a($input->domElement, "DOMNode")) {
            throw new Exception("Tipo dell'input.document errato, previsto: DOMNode, ricevuto: " .
                (is_object($input->domElement) ? get_class($input->domElement) : gettype($input->domElement)));
        }
    }
}