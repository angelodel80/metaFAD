<?php

class metafad_solr_Listener extends GlizyObject
{
    private static $fieldOp = '->';
    private static $valueOp = '!';
    private static $regexSelector = '/\[(([1-9]\d*)|\!)\]/';

    private $documents = array();
    private $total;
    private $count = 0;
    private $searchFound = array();

    function __construct()
    {
        $this->addEventListener('insertRecord', $this);
        $this->addEventListener('insertRecordFE', $this);
        $this->addEventListener('insertData', $this);
        $this->addEventListener('deleteRecord', $this);
        $this->addEventListener('commit', $this);
        $this->addEventListener('deleteAll', $this);
        $this->addEventListener('commitRemainingFE', $this);
    }


    /**
     * Restituisce un array di valori (NON VUOTI) cercati secondo il codice che seleziona i campi voluti.
     *
     * ---
     *
     * Il codice funziona nella maniera seguente:
     *
     * $code == 'codice' => prende tutti i valori con chiave associata di nome 'codice' (a prescindere dalla profondità)
     *
     * $code == 'data->anno' => prende tutti i valori con chiave associata 'anno' il cui genitore ha chiave associata 'data'
     *
     * $code == 'data[!]->anno' => prende un singolo valore non vuoto di 'data->anno'
     *
     * $code == 'data[!]->recente->anno' => prende un singolo valore non vuoto di 'data->recente->anno'
     *
     * $code == 'data->recente[!]->anno' => per ogni data, prende un singolo valore non vuoto di 'recente->anno', contenuto in un 'data'
     *
     * ---
     *
     * In riassunto, l'operatore [!] è un'operatore di "strozzatura" della ricerca: tutto ciò che non è precedente a tale
     *
     * operatore, verrà ridotto ad un singolo valore non vuoto.
     *
     *
     * ---
     *
     * Altro avviso: l'ultimo campo è previsto che sia un campo "primitivo" (stringa, numero, ecc...).
     *
     *
     * @param string $code codice per indirizzare
     * @param mixed $obj oggetto da attraversare per trovare i valori
     * @param string $selector simbolo da utilizzare al momento dell'inizio della funzione: '!' se si vuole strozzare il risultato
     * @return array Array di valori non vuoti
     */
    public function searchExtended($code, $obj, $selector = '')
    {
        $ret = array();

        if ($obj && (is_array($obj) || is_object($obj))) {
            $split = explode(self::$fieldOp, $code, 2);
            $curField = $split[0];
            $nextFields = count($split) > 1 ? $split[1] : null;
            $selectors = array();
            preg_match(self::$regexSelector, $curField, $selectors);
            $curField = preg_replace(self::$regexSelector, "", $curField);
            $selector = count($selectors) > 0 ? current($selectors) : $selector;
            $selector = $selector == null ? null : trim(str_replace(array("[", "]"), "", $selector));

            $selected = $this->searchField($curField, $obj);

            if ($nextFields != null) {
                $keys = array_keys($selected);
                $i = count($keys);
                $found = false;

                while ($i-- > 0 && ($selector != self::$valueOp || !$found)) {
                    $ret = array_merge($ret, $this->searchExtended($nextFields, $selected[$i]));
                }

            } else {
                $ret = array_merge($ret, $selected);
            }
        }

        $ret = array_filter($ret, function ($a) {
            return !is_null($a) && $a != '';
        });

        return $selector == self::$valueOp ? (count($ret) > 0 ? array(current($ret)) : array()) : $ret;
    }

    /**
     * Restituisce tutti i valori non vuoti all'interno di un oggetto con il nome del campo voluto
     * @param string $field Nome del campo ricercato
     * @param mixed $object Oggetto da attraversare per restituire i valori
     * @return array Tutti i valori (primitivi e non) che provengono da un campo con il nome voluto
     */
    public function searchField($field, $object)
    {
        $ret = array();

        if ($object && (is_array($object) || is_object($object))) {
            foreach ($object as $k => $v) {
                if ($k == $field && $v != "" && !is_null($v) && (!is_array($v) || count($v) > 0)) {
                    if (is_array($v)) {
                        $ret = array_merge($ret, $v);
                    } else
                        $ret[] = $v;
                } else if (is_array($v)) {
                    foreach ($v as $value) {
                        $ret = array_merge($ret, $this->searchField($field, $value));
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * @param $object
     * @return array
     */
    private function flatten($object)
    {
        $ret = $object;

        if (is_array($object) || is_object($object)){
            $arr = json_decode(json_encode($object), true);
            $ret = array_filter(array_values($arr ? $arr : array()), function($a){return $a;});
        } else {
            $ret = $object ? array($object) : array();
        }

        return $ret ? $ret : array();
    }

    private function search($key, $datas)
    {
        if ($datas && !$this->searchFound && !is_string($datas)) {
            foreach ($datas as $k => $v) {
                if ($k == $key && !is_array($v)) {
                    $this->searchFound = $v;
                    break;
                } else if (is_array($v)) {
                    foreach ($v as $value) {
                        $this->search($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Funzione per correzioni di Mapping legate a delle POLODEBUG:
     * <ul>
     * <li>POLODEBUG-481, BE, Punto 1</li>
     * </ul>
     * @param $doc
     * @param $data
     */
    private function correctDoc($doc, $data)
    {
        //POLODEBUG-481, BE, Punto 1
        if (in_array('archivi.models.ProduttoreConservatore', array($data->__model, $data->document_type)) && strtolower($data->tipologiaChoice) == 'persona'){
            $persDens = $data->persona_denominazione;
            $denomBlock = count($persDens) > 0 ? $persDens[0] : new stdClass();

            $doc->denominazione_s = implode(", ", array_filter(array($denomBlock->entitaDenominazione, $denomBlock->persona_nome)));
        }
    }

    public function insertData($evt)
    {
        $data = $evt->data['data'];
        $option = isset($evt->data['option']) ? $evt->data['option'] : array();
        $this->sendRequest('add', $data, $option);
    }

    protected function setSolrField($doc, $solrField, $value)
    {
        if (strpos($solrField, ',') !== false) {
            $solrFields = explode(',', $solrField);
            foreach ($solrFields as $solrField) {
                $doc->$solrField = $value;
            }
        } else {
            $doc->$solrField = $value;
        }
    }

    protected function advancedMapping($fieldName, $data)
    {
        $implode = true;
        $romanToInteger = false;
        $onlyYear = false;

        // se $fieldName finisce per '[]'
        if (strrpos($fieldName, '[]') == strlen($fieldName)-2) {
            $fieldName = substr($fieldName, 0, strlen($fieldName)-2);
            $implode = false;
        };

        if (strrpos($fieldName, ':romanToInteger')) {
            $fieldName = str_replace(':romanToInteger', '', $fieldName);
            $romanToInteger = true;
        };

        if (strrpos($fieldName, ':onlyYear')) {
            $fieldName = str_replace(':onlyYear', '', $fieldName);
            $onlyYear = true;
        };

        $query = substr($fieldName, 1);

        // | prende il primo valorizzato
        // , concatena i valori 
        if (strpos($query, ',') !== false) {
            $operator = ',';
        } else {
            $operator = '|';
        }

        $queries = array_filter(array_map(function($a){return trim($a);}, explode($operator, $query))); 
        $vals = array();
        foreach ($queries as $q){
            $curArray = array_filter($this->flatten($this->searchExtended($q, $data)));
            
            if ($operator == ',') {
                $vals = array_merge($curArray, $vals);
            } else {
                $vals = count($vals) > 0 ? $vals : $curArray;
            }
        }

        if ($romanToInteger) {
            $romanService = __ObjectFactory::createObject('metafad.common.helpers.RomanService');
            $vals = array_map(function($o) use ($romanService) { return $romanService->romanToInteger($o); }, $vals);
        }

        if ($onlyYear) {
            $vals = array_map(function($s) { return substr($s, 0, 4); }, $vals);
        }

        if ($implode) {
            $this->searchFound = implode(", ", array_filter($vals));
        } else {
            $this->searchFound = $vals;
        }

        return $this->searchFound;
    }

    /**
     * @param $evt
     */
    public function insertRecord($evt)
    {
        $data = $evt->data['data'];
        $model = $data->__model;
        $option = isset($evt->data['option']) ? $evt->data['option'] : array();
        $this->total = $option['total'];

        $beHelper = $this->getSolrBeHelper();
        $document = $this->getModelFactory($model);
        $solrModel = $document->getSolrDocument();

        $solrFieldMapping = array();
        if (method_exists($document, 'getSolrBEFieldMapping')) 
        {
            $solrFieldMapping = $document->getSolrBEFieldMapping();
        }

        $doc = new stdClass();
        foreach ($solrModel as $fieldName => $solrField) {
            // TODO gestire anche questi casi nel searchExtended
            if ($fieldName == 'SGLT') {
                $value = $data->SGL[0]->SGLT ?: $data->SGL[0]->SGLA;
            } else if ($fieldName == 'SGTI') {
                $el = 'SGTI-element';
                $value = $data->SGT[0]->SGTI[0]->$el;
            } else if ($fieldName == 'updateDateTime') {
                $updateDateTime = new DateTime();
                $value = $updateDateTime->format('Y-m-d H:i:s');
            } else if ($solrField == 'document_type_t') {
                $value = $model;
            } else if (strpos($fieldName, '@') === 0){ //Inizia con @, la levo e inizio la ricerca "avanzata"
                $value = $this->advancedMapping($fieldName, $data);
            } else if ($this->isICCDField($fieldName)) {
                $hits = array_filter($this->flatten($this->searchExtended($fieldName, $data)));
                $value =  count($hits) > 0 ? current($hits) : "";
            } else if (array_key_exists($fieldName, $solrFieldMapping)) {
                $valuesForSolr = $solrFieldMapping[$fieldName];
                $value = $valuesForSolr[$data->$fieldName];
            }
            else {
                $value = $data->$fieldName;
            }

            if ($value) {
                $this->setSolrField($doc, $solrField, $value);
            }
        }

        $this->correctDoc($doc, $data);

        //MAPPING per ricerca avanzata BE
        $solrRAModel = (!method_exists($document, 'getBeMappingAdvancedSearch')) ? null : $document->getBeMappingAdvancedSearch();
        if ($solrRAModel['beMapping']) {
          $docBe = $beHelper->mapping($solrRAModel,$data);
          foreach($docBe as $kk => $vv)
          {
            if($vv)
            {
              $doc->$kk = $vv;
            }
          }
        }

        $doc->instituteKey_s = ($data->instituteKey) ?: metafad_usersAndPermissions_Common::getInstituteKey();
        if ($option['reindex'] === true && $doc) {
            $this->count++;
            $this->documents[] = $doc;
            if (sizeof($this->documents) === 50 || $this->count == $this->total) {
                $this->sendRequest('add', $this->documents, $option);
                $this->documents = array();
            }
        } else if ($doc) {
            $this->sendRequest('add', $doc, $option);
        }
    }

    public function insertRecordFE($evt)
    {
        $data = $evt->data['data'];
        $model = $data->__model;
        $option = isset($evt->data['option']) ? $evt->data['option'] : array();
		$isAut = $option['aut'];
        $option['url'] = ($isAut) ? __Config::get('metafad.solr.iccdaut.url') : __Config::get('metafad.solr.iccd.url');

        $feHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.FeHelper');
        $hasImageHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.HasImageHelper');
        // $option =  isset($evt->data['option']) ? $evt->data['option'] : array();
        $document = org_glizy_objectFactory::createModel($model);
        $solrModel = $document->getFESolrDocument();

        if($data->simpleForm === '' || is_string($data->simpleForm))
        {
          die;
        }

        if ($solrModel['feMapping']) {
            $doc = new stdClass();
            $doc->id = $data->__id;
            $doc->type_nxs = 'iccd';
			$doc->institutekey_s = ($data->instituteKey) ?: metafad_usersAndPermissions_Common::getInstituteKey();

			if(!$isAut)
			{
	            if($data->visibility === '0')
	            {
	              //Non voglio mostrare il record in FE e se c'è devo rimuoverlo
	              $proxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy');
	              $proxy->deleteFromFE($data->__id);
	              return;
	            }
	            else if($data->visibility)
	            {
	              $doc->visibility_nxs = $data->visibility;
	            }

	            //Prendo le info per la prima immagine da avere in SOLR
	            $fi = org_glizy_objectFactory::createObject('metafad.viewer.helpers.FirstImage');
	            $firstImage = $fi->execute($doc->id,'iccd');
	            if($firstImage)
	            {
	              $doc->digitale_idpreview_s = $firstImage['firstImage'];
	              $doc->digitale_idpreview_t = $firstImage['firstImage'];
	            }

	            $ecommHelper = org_glizy_objectFactory::createObject('metafad_ecommerce_helpers_EcommerceToSolrHelper');
	            $ecommerceInfo = $ecommHelper->getEcommerceInfo($data,'iccd');
	            if($ecommerceInfo)
	            {
	              $doc->ecommerce_nxs = $ecommerceInfo;
	            }

	            $metaindiceHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.MetaindiceHelper');
	            $area_digitale = $metaindiceHelper->mappingDigitalField($data,'iccd');
	            if($area_digitale)
	            {
	              $doc->area_digitale_ss = $area_digitale;
	              $doc->area_digitale_txt = $area_digitale;
	            }

	            if($data->RV)
	            {
	              if($data->RV[0]->RVE[0]->RVEL)
	              {
	                $doc->level_s = $data->RV[0]->RVE[0]->RVEL;
	              }
	            }

				//Informazione sulla presenza o meno di digitale collegato
	            $hasImage = $hasImageHelper->hasImage($data,'iccd');
	            if($hasImage)
	            {
	              $doc->digitale_s = 'true';
	            }

	            //salvo riferimento a BID SBN se presente
	            if($data->BID)
	            {
	              $doc->linkediccd_s = $data->BID;
	            }

			}

			//Mapping per scheda di dettaglio
            $detailDoc = $feHelper->detailMapping($data);
            foreach($detailDoc as $kk => $vv)
            {
              $doc->$kk = $vv;
            }

			//Mapping campi di ricerca
			$mappingFields = json_decode($solrModel['feMapping']);
			$searchDoc = $feHelper->searchFieldsMapping($mappingFields,$data);
			foreach($searchDoc as $kk => $vv)
			{
			  $doc->$kk = $vv;
			}
        }

        //TODO è temporaneo finché non viene fornito indice classico, ora ci
        //serve per indicizzare solo il dettaglio delle aut, l'indice è generale
        //RIMUOVERE quando sarà presente tale informazione
		if(__Config::get('metafad.fe.search.hasCustomServices'))
		{
	        if($model == 'AUT300.models.Model' || $model == 'AUT400.models.Model')
	        {
	          $doc = new stdClass();
	          $doc->id = $data->__id;
	          $doc->type_nxs = 'aut';
	          $detailDoc = $feHelper->detailMapping($data);
	          foreach($detailDoc as $kk => $vv)
	          {
	            $doc->$kk = $vv;
	          }
	          $option['url'] = __Config::get('metafad.solr.iccdaut.url');
	        }
		}

        if ($option['reindex'] === true && $doc) {
            $this->count++;
            $this->documents[] = $doc;
            if (sizeof($this->documents) === 50 || $this->count == $this->total) {
                $this->sendRequest('add', $this->documents, $option);
                $this->documents = array();
            }
        } else if ($doc) {
            $this->sendRequest('add', $doc, $option);
        }
    }

    public function commit($evt)
    {
        $this->sendRequest('commit', null);
        $this->documents = array();
    }

    public function commitRemainingFE($evt)
    {
        if (sizeof($this->documents) > 0) {
            $this->sendRequest('add', $this->documents, array('reindex' => true,'commit'=>true,'url'=> $evt->data['option']['url']));
        }
        $this->documents = array();
    }

    public function deleteRecord($evt)
    {
        $data = $evt->data;
        $option = (is_string($data)) ? null : $data['option'];
        $this->sendRequest('delete', $data, $option);
    }

    public function deleteAll($evt)
    {
        $this->sendRequest('deleteAll', $evt->data['data'], $evt->data['option']);
    }

    public function sendRequest($action, $doc, $option = array())
    {
        if ($action == 'add' || $action == 'queue') {
            $command = 'update/json';
            $json = array(
                'add' => array(
                    'doc' => $doc,
                    'boost' => 1.0,
                    'overwrite' => true,
                    'commitWithin' => 1000
                )
            );

            if ($option['commit']) {
                $json['commit'] = new StdClass();
                $optionCommit = '&commit=true';
            }

            if ($action == 'queue') {
                $this->documents[] = trim(json_encode($json), '{}') . '}';
                return;
            }

            if ($option['reindex'] === true) {
                $json = $doc;
                $optionCommit = '&commit=true';
            }
        } else if ($action == 'commit') {
            $command = 'update/json';
            if (!empty($this->documents)) {
                $json = '{' . implode(',', $this->documents) . ', "commit":{}}';
            } else {
                $json = '{"commit":{}}';
            }
        } else if ($action == 'delete') {
            $command = 'update/json';
            $id = (is_array($doc)) ? $doc['id'] : $doc;
            $json = array(
                'delete' => array('query' => 'id:' . $id),
                'commit' => new StdClass()
            );
        } else if ($action == 'deleteAll') {
            $command = 'update/json';
            $json = array(
                'delete' => array(
                    'query' => $option['query'],
                ),
                'commit' => new StdClass()
            );
        }

        $postBody = is_string($json) ? $json : json_encode($json);
        if($postBody)
        {
          $url = ($option['url']) ? ($option['url']) : __Config::get('metafad.solr.url');
          $request = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest',
              $url . $command . '?wt=json' . $optionCommit,
              'POST',
              $postBody,
              'application/json'
          );

          $request->execute();
        }
    }

    /**
     * Euristica grossolana per vedere se è un campo di ICCD
     * @param $fieldName
     * @return bool
     */
    private function isICCDField($fieldName)
    {
        return (strlen($fieldName) <= 6 && strtoupper($fieldName) == $fieldName);
    }

    /**
     * @return metafad_solr_helpers_BeHelper
     */
    private function getSolrBeHelper()
    {
        return org_glizy_objectFactory::createObject('metafad.solr.helpers.BeHelper');
    }

    /**
     * @param $model
     * @return metafad_common_models_ActiveRecordDocument
     */
    private function getModelFactory($model)
    {
        return org_glizy_objectFactory::createModel($model);


    }
}
