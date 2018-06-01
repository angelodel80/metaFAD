<?php

class metafad_common_importer_operations_ICCD_LinkAutBib extends metafad_common_importer_operations_LinkedToRunner
{
    protected
        $duplicates,
        $iccdFormProxy,
        $uniqueIccdIdProxy,
        $iccdProxy,
        $refsField,
        $altRefsField,
        $refHeader,
        $refNumber,
        $returnSigla,
        $modelName;

    /**
     * Si aspetta:
     * refsField campo di riferimento primario al link (es. AUT o BIB)
     * altRefsField campo di riferimento secondario ai link (es. AU o DO)
     * refHeader campo di descrizione al riferimento (es. AUTH o BIBH)
     * refNumber campo di elenco al riferimento (es. AUTN o BIBM o BIBA)
     * returnSigla campo di restituzione valore (es. __AUT o __BIB)
     * modelName nome del modello relativo al tipo di scheda da salvare (es AUT300.models.Model o BIB300.models.Model)
     * metafad_common_importer_operations_TRCToStdClass constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     */
    public function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        parent::__construct($params, $runnerRef);

        $this->refsField = $params->refsField ?: "";
        $this->altRefsField = $params->altRefsField ?: "";
        $this->refHeader = $params->refHeader ?: "";
        $this->refNumber = $params->refNumber ?: "";
        $this->returnSigla = $params->returnSigla ?: "";
        $this->modelName = $params->modelName ?: "";

        $this->duplicates = $this->getOrSetDefault("duplicates", array());
        $this->iccdProxy = $this->getOrSetDefault("iccdProxy", __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy'));
        $this->iccdFormProxy = $this->getOrSetDefault("iccdFormProxy", __ObjectFactory::createObject('metafad.modules.iccd.models.proxy.IccdFormProxy'));
        $this->uniqueIccdIdProxy = $this->getOrSetDefault("uniqueIccdIdProxy", __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.UniqueIccdIdProxy'));
    }

    /**
     * Riceve:
     * data = dati in formato finale (precedente al salvataggio)
     *
     * Output:
     * data = dati in formato finale (precedente al salvataggio)
     *
     * @param stdClass $input
     * @return stdClass solito input ma con "data" modificato
     * @throws Exception se il file non esiste
     */
    public function execute($input)
    {
        $data = $input->data;

        //TODO: Ricordati questo: $fieldName = ($this->version == '400' ? 'BIBM' : 'BIBA');

        $this->saveRefs(
            $data,
            $this->refsField,
            $this->altRefsField,
            $this->refHeader,
            $this->refNumber,
            $this->returnSigla,
            $this->modelName
            );

        $this->runner->set("duplicates", $this->duplicates);

        return $input;
    }

    /**
     * @param $data stdClass
     * @param $refsField string
     * @param $altRefsField string
     * @param $header string
     * @param $number string
     * @param $returnSigla string
     * @param $modelName string
     */
    protected function saveRefs(&$data, $refsField, $altRefsField, $header, $number, $returnSigla, $modelName)
    {
        //$refsField = "AUT";
        //$altRefsField = "AU";
        //$returnSigla = "__AUT";
        //Salvo i riferimenti relativi ad AUT
        $refs = $data->{$refsField};
        if (!empty($refs)) {
            $autIDs = $this->getRefIDs($refs, $header, $number, $returnSigla, $modelName);

            $count = count($refs);
            for ($i = 0; $i < $count; $i++){
                $refs[$i]->{$returnSigla} = $autIDs[$i][$returnSigla];
            }
        } else {
            $altRefs = $data->{$altRefsField};
            $refs = $altRefs[0]->{$refsField};
            if (!empty($refs)) {
                $autIDs = $this->getRefIDs($refs, $header, $number, $returnSigla, $modelName);

                $count = count($refs);
                for ($i = 0; $i < $count; $i++) {
                    $refs[$i]->{$returnSigla} = $autIDs[$i][$returnSigla];
                }
            }
        }
    }

    protected function getRefIDs($refs, $refHeader, $refNumber, $returnSigla, $modelName)
    {
        //$modelName = $modelName ?: ("AUT{$this->version}.models.Model");
        //$header = 'AUTH';
        //$number = "AUTN";
        //$returnSigla = "__AUT";

        $ids = array();

        foreach ($refs as $ref) {
            $arr = (array)$ref;
            if (!empty($arr)) {
                $it = org_glizy_ObjectFactory::createModelIterator($modelName, 'all', array('filters' => array($refHeader => $ref->{$refHeader})));
                if ($it->count()) {
                    $ar = $it->current();

                    $ids[] = array($returnSigla => array('id' => $ar->document_id, 'text' => $ar->{$refNumber} . ' - ' . $ar->{$refHeader}));
                } else {
                    $ref->__id = 0;
                    $ref->__model = $modelName;
                    $result = $this->addContent($ref, false, true);

                    $ids[] = array($returnSigla => array('id' => $result['__id'], 'text' => $ref->{$refNumber} . ' - ' . $ref->{$refHeader}));
                }
            }
        }

        return $ids;
    }

    /**
     * Salvataggio tramite proxy delle schede
     *
     * @param object $obj Oggetto contenente i dati della scheda da salvare
     *
     * @return array del risultato della scheda salvata
     */
    private function addContent($obj, $temp, $autbib = false)
    {
        $this->utf8_encode_deep($obj);

        //controllo se la scheda in questione esiste già
        $uniqueIccdId = $this->uniqueIccdIdProxy->createUniqueIccdId($obj);
        $obj = $this->uniqueIccdIdProxy->checkUnique($obj, $uniqueIccdId);

        if ($autbib) {
            if ($obj->__id != 0) {
                return array('__id' => $obj->__id);
            }
        } elseif (is_array($this->duplicates) && ($obj->__id != 0 or array_key_exists($uniqueIccdId, $this->duplicates))) //MZ aggiunto is_array
            $this->duplicates[$uniqueIccdId]++;
        else
            $this->duplicates[$uniqueIccdId] = 1;

        $rv = $obj->RV[0];

        if ($rv->RSE) {
            foreach ($rv->RSE as $i => $rse) {
                $objtemp = new StdClass();
                $objtemp->id = $this->ICCDfind('RSEC', $rse->RSEC);
                $objtemp->text = $rse->RSEC;
                $rse->RSEC = $objtemp;
            }
        }

        if ($rv->ROZ) {
            foreach ($rv->ROZ as $i => $roz) {
                $objtemp = new StdClass();
                $objtemp->id = $this->ICCDfind('ROZ-element', $roz->{'ROZ-element'});
                $objtemp->text = $roz->{'ROZ-element'};
                $roz->{'ROZ-element'} = $objtemp;
            }
        }

        //MZ commentata riga salvataggio ed inserito nuovo proxy
        //$result = $this->contentProxy->saveContent($obj, false);
        //$iccdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy');
        $result = $this->iccdProxy->save($obj, true);

        return $result;
    }

    /**
     * Codifica UTF-8 di tutti i dati della scheda
     *
     * @param object $input Oggetto contenente i dati della scheda da codificare
     */
    function utf8_encode_deep(&$input)
    {
        if (is_string($input)) {
            $input = utf8_encode($input);
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                $this->utf8_encode_deep($value);
            }

            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));

            foreach ($vars as $var) {
                $this->utf8_encode_deep($input->$var);
            }
        }
    }

    private function ICCDfind($fieldName, $term)
    {
        if ($term != '') {
            $result = $this->iccdFormProxy->findTerm($fieldName, null, null, $term, null);
            return count($result) > 0 ? $result[0]['id'] : null;
        } else {
            return null;
        }
    }

    public function validateInput($input)
    {
        // TODO: Change the autogenerated stub
    }

}