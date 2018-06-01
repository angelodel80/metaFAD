<?php

class metafad_common_importer_operations_EADXmlToJson extends metafad_common_importer_operations_XmlToJson
{
    protected $schemi = array();
    protected $descrTable = array();

    /**
     * metafad_common_importer_operations_ReadXML constructor.
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>suppress = Ignora gli errori nella execute (facoltativo)</li>
     * <li>schemafile = Nome del file JSON da usare per la mappatura antecedente a quella specifica</li>
     * <li>ca_schemafile = Nome del file JSON da usare per la mappatura di una CA</li>
     * <li>ud_schemafile = Nome del file JSON da usare per la mappatura di una UD</li>
     * <li>ua_schemafile = Nome del file JSON da usare per la mappatura di una UA</li>
     * </ul>
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     * @throws Exception se params non è conforme a quanto scritto in questa descrizione
     */
    function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        if ($params->ca_schemafile){
            $this->schemi['CA'] = json_decode(file_get_contents($params->ca_schemafile));
        }
        if ($params->ud_schemafile){
            $this->schemi['UD'] = json_decode(file_get_contents($params->ud_schemafile));
        }
        if ($params->ua_schemafile){
            $this->schemi['UA'] = json_decode(file_get_contents($params->ua_schemafile));
        }

        $this->descrTable = array(
            "complesso-di-fondi" => 'CA',
            "superfondo" => 'CA',
            "fondo" => 'CA',
            "sub-fondo" => 'CA',
            "sezione" => 'CA',
            "serie" => 'CA',
            "sottoserie" => 'CA',
            "sottosottoserie" => 'CA',
            "collezione-raccolta" => 'CA',
            "unita" => 'UA',
            "sottounita" => 'UA',
            "sottosottounita" => 'UA',
            "documento-principale" => 'UD',
            "documento-allegato" => 'UD'
        );

        parent::__construct($params, $runnerRef);
    }


    /**
     * Riceve una stdClass con:<br>
     * <ul>
     * <li>domElement = Oggetto DOMElement</li>
     * <li>idDomElement = Identificatore univoco associato all'oggetto DOMElement</li>
     * <li>schemafile = File JSON da usare invece di quello passatogli via parametro (facoltativo)</li>
     * </ul>
     * Restituisce una stdClass con:<br>
     * <ul>
     * <li>data = stdClass che rappresenta il nodo da salvare</li>
     * <li>idDomElement = stesso dell'input</li>
     * </ul>
     *
     * @param stdClass $input
     * @throws Exception se suppressErrors è false (sennò ignora l'eccezione)
     * @return stdClass contenente i dati da salvare (si raggiungono con la chiave "data")
     */
    function execute($input)
    {
        $out = new stdClass();

        $this->schemaFile = $input->schemafile ? json_decode(file_get_contents($input->schemafile)) : $this->schemaFile;

        if ($this->schemaFile === null) {
            throw new Exception("Schema passato all'XmlToJson formattato male o nullo: " . json_last_error_msg());
        }
        $output = parent::execute($input);
        $output->idDomElement = $input->idDomElement;

        $this->schemaFile = $this->schemi[$this->descrTable[$output->data->livelloDiDescrizione]];
        if ($this->schemaFile){
            $out = parent::execute($input);
        } else {
            throw new Exception("Schema passato all'XmlToJson formattato male o nullo: " . json_last_error_msg());
        }

        foreach ($out->data as $k => $v){
            $output->data->{$k} = $v;
        }

        return $output;
    }

    function validateInput($input)
    {
        if (!is_a($input->domElement, "DOMNode")) {
            throw new Exception("Tipo dell'input.document errato, previsto: DOMNode, ricevuto: " .
                (is_object($input->domElement) ? get_class($input->domElement) : gettype($input->domElement)));
        }
    }
}