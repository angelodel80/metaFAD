<?php

/**
 * Class metafad_common_importer_operations_ReadXML
 * Legge l'XML indicato dal filename e tenta di restituire un DOMDocument
 */
class metafad_common_importer_operations_ReadXML extends metafad_common_importer_operations_LinkedToRunner
{
    protected $filename = "";
    protected $suppressErrors = false;
    protected $keepDefaultNS = false;

    /**
     * metafad_common_importer_operations_ReadXML constructor.
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>suppress = Ignora gli errori nella execute</li>
     * <li>filename = Filename dell'XML da caricare</li>
     * <li>keepDefaultNS = "true" se e solo se si vuole mantenere il namespace di default</li>
     * </ul>
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     */
    function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        $this->suppressErrors = $params->suppress === "true";
        $this->keepDefaultNS = $params->keepDefaultNS === "true";
        $this->filename = $params->filename ?: $this->filename;
        parent::__construct($params, $runnerRef);
    }

    /**
     * Restituisce una stdClass con:<br>
     * <ul>
     * <li>document = Oggetto DOMDocument con il file caricato</li>
     * </ul>
     * @param stdClass $input
     * @throws Exception se suppressErrors è false (sennò ignora l'eccezione)
     * @return stdClass
     */
    function execute($input)
    {
        $dom = new DOMDocument();
        try{
            if (!$this->keepDefaultNS){ //Orribile workaround per evitare gli xmlns senza prefisso
                $dom->loadXML(str_replace("xmlns=", "xmlns:faked=", file_get_contents($this->filename)));
            } else {
                $dom->load($this->filename);
            }
            $dom->preserveWhiteSpace = false;
        } catch (Exception $ex){
            if (!$this->suppressErrors){
                throw $ex;
            }
        }

        $ret = new stdClass();
        $ret->document = $dom;

        return $ret;
    }

    function validateInput($input)
    {
        return "";
    }
}