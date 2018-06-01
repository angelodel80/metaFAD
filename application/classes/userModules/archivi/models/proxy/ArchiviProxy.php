<?php
set_time_limit(0);

class archivi_models_proxy_ArchiviProxy extends metafad_common_models_proxy_SolrQueueProxy
{
    protected static $sigle = null;
    protected $application = null;
    protected $profiling = false;

    /**
     * Serve a riprovare il salvataggio per la bozza
     * @var bool
     */
    protected $retry = false;
    /**
     * Serve per forzare l'update della visibilità anche ai livelli sottostanti.
     * Messa a false per evitare chiamate ridondanti verso il basso.
     * @var bool
     */
    protected $updateVisibility = true;
    protected $bench = array();
    /**
     * @var $arcFEHelper metafad_solr_helpers_ArchiveFEHelper
     */
    protected $arcFEHelper = null;
    /**
     * @var org_glizycms_contents_models_proxy_ModuleContentProxy
     */
    protected $proxy = null;
    private $stack = array();
    protected $hasImageHelper = null;

    /**
     * @return boolean
     */
    public function isRetry()
    {
        return $this->retry;
    }

    /**
     * Fluent setter
     * @param boolean $updateVisibility
     * @return $this
     */
    public function setUpdateVisibility($updateVisibility)
    {
        $this->updateVisibility = $updateVisibility;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUpdateVisibility()
    {
        return $this->updateVisibility;
    }

    /**
     * Fluent setter
     * @param $retry
     * @return $this
     */
    public function setRetryWithDraftOnInvalidate($retry)
    {
        $this->retry = $retry;
        return $this;
    }

    function __construct($profileSave = false)
    {
        $this->application = org_glizy_ObjectValues::get('org.glizy', 'application');
        $this->profiling = $profileSave === true;
        $this->initSigle();

        $this->arcFEHelper = $this->arcFEHelper ?: __ObjectFactory::createObject('metafad.solr.helpers.ArchiveFEHelper');
        $this->proxy = __ObjectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $this->hasImageHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.HasImageHelper');
    }

    public function initSigle()
    {
        if (!self::$sigle) {
            $prefix = "archivi.models.";
            self::$sigle = array(
                $prefix . "ComplessoArchivistico" => "CA",
                $prefix . "UnitaArchivistica" => "UA",
                $prefix . "UnitaDocumentaria" => "UD",
                $prefix . "FonteArchivistica" => "FA",
                $prefix . "ProduttoreConservatore" => "ENT",
                $prefix . "SchedaBibliografica" => "SB",
                $prefix . "SchedaStrumentoRicerca" => "SR"
            );
        }
    }

    public function validate($data)
    {
        $this->buildProdConsField($data);

        $catExporter = __ObjectFactory::createObject('metafad.modules.exporter.services.catexporter.CatExporter');
        $result = $catExporter->validate($data);
        if ($result === true) {
            $data->root = (!$data->parent) ? 'true' : 'false';
            $data->isValid = 1;
            $result = $this->proxy->saveContent($data, true);

            $this->appendDocumentToData($data);
            $this->sendDataToSolr($data, true);

            if ($result['__id']) {
                return array('set' => $result);
            } else {
                return array('errors' => $result);
            }
        } else {
            return array('errors' => array('Scheda non valida per CAT-SAN'));
        }
    }

    protected function createModel($id = null, $model)
    {
        $document = __ObjectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }

    /**
     * Esegue il salvataggio (prima presente in archivi.controllers.ajax.Save)
     * @param $data StdClass
     * @param $invertRelation bool (Default TRUE) serve per chiamare il proxy di inversione delle relazioni
     * @return mixed Restituisce $result
     */
    public function save($data, $invertRelation = true)
    {
        $isDraft = false;
        $data->isValid = 0;
        $data->root = (!$data->parent) ? 'true' : 'false';

        return $this->saveProcedure($data, $invertRelation, $isDraft);
    }

    protected function handleVisibility($data)
    {
        $visibilityModels = array(
            "archivi.models.ComplessoArchivistico",
            "archivi.models.UnitaArchivistica",
            "archivi.models.UnitaDocumentaria"
        );

        if (!$data->__id || !$data->__model || !in_array($data->__model, $visibilityModels) || !$this->updateVisibility) { //TODO: L'ultimo serve ad evitare che si metta a fare il save di tutti i figli
            return;
        }

        $old = $this->createModel($data->__id, $data->__model);

        $visHelper = __ObjectFactory::createObject('metafad.common.helpers.VisibilityHelper');

        if (!$visHelper->compareVisibilities($old->visibility, $data->visibility)) {
            $jobFactory = __ObjectFactory::createObject('metacms.jobmanager.JobFactory');
            $jobFactory->createJob(
                'archivi_services_VisibilityService',
                array(
                    'id' => $data->__id,
                    'model' => $data->__model,
                    'visibility' => $data->visibility
                ),
                'Cambio visibilità',
                'BACKGROUND'
            );

            $data->visibility = $old->visibility;
        }

    }

    /**
     * @param $data
     * @param $invertRelation
     * @param $isDraft
     * @return array
     */
    protected function saveProcedure($data, $invertRelation, $isDraft)
    {
        $this->handleVisibility($data);

        $this->benchStart('saveObject');
        $this->buildProdConsField($data);
        $res = $this->saveObject($data, $isDraft === true);
        $this->benchEnd('saveObject');

        if (key_exists("errors", $res) && $this->isRetry()){
            $isDraft = true;
            $this->benchStart('saveObject');
            $res = $this->saveObject($data, $isDraft === true);
            $this->benchEnd('saveObject');
        }

        $this->benchStart('updateId');
        $res = $this->updateIdentificativi($data, $res, $isDraft === true);
        $this->benchEnd('updateId');

        $this->appendDocumentToData($data);

        if ($invertRelation) {
            //$invRelProxy = new archivi_models_proxy_InverseRelationProxy();
            //$invRelProxy->insertInverseRelation($data);
        }

        $this->benchStart('solr');
        $this->sendDataToSolr($data, true);
        $this->benchEnd('solr');

        if ($this->profiling === true) {
            foreach ($this->bench as $k => $v) {
                echo "'$k' took {$v['time']}ms\n<br>\n";
            }
        }
        $this->bench = array();

        return $res;
    }

    public function benchStart($str)
    {
        if ($this->profiling === true) {
            $this->bench[$str]['start'] = microtime(true);
        }
    }

    // POLODEBUG-219 - ereditarietà del primo soggetto produttore
    protected function inheritProducer($id, $produttori)
    {
        $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.ComplessoArchivistico')
            ->where('parent', $id);
        
        foreach ($it as $ar) {
            if (!$ar->produttori) {
                $childProduttori = array();
            } else {
                $childProduttori = $ar->produttori;
            }
            $childProduttori[0] = $produttori[0];
            $ar->produttori = $childProduttori;
            $ar->saveCurrentPublished();

            // ricorsione sulle CA figlie
            $this->inheritProducer($ar->getId(), $produttori);
        }
    }

    /**
     * @param $data
     * @param $isDraft
     * @return array
     */
    protected function saveObject($data, $isDraft = false)
    {
        $voceIndice = array(
            "archivi.models.antroponimi",
            "archivi.models.toponimi",
            "archivi.models.enti"
        );

        if ($data->__id && !in_array(strtolower($data->__model), $voceIndice)) {
            $data->identificativo = $data->acronimoSistema . " " . self::$sigle[$data->__model] . " " . $data->__id;
            $data->codiceIdentificativoSistema = $data->__id;

            // POLODEBUG-219 - ereditarietà del primo soggetto produttore
            if (!empty($data->produttori[0])) {
                $this->inheritProducer($data->__id, $data->produttori);
            }
        }

        $data->_denominazione = $this->extractTitleFromStdClass($data);
        $data->instituteKey = $data->instituteKey ?: metafad_usersAndPermissions_Common::getInstituteKey();

        $result = array();
        if (in_array(strtolower($data->__model), $voceIndice)){
            $ar = __ObjectFactory::createModelIterator($data->__model)
                ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
                ->where("intestazione", $data->intestazione)
                ->first();
            if ($ar){
                $result['__id'] = $ar->getId();
            } else {
                $result = $this->proxy->saveContent($data, __Config::get('glizycms.content.history'), $isDraft === true);
            }
        } else {
            if ($data->__id) {
                $ar = org_glizy_objectFactory::createModel('archivi.models.Model');
                $ar->load($data->__id, $isDraft ? 'DRAFT' : 'PUBLISHED');
                if ($data->denominazione != $ar->denominazione) {

                    $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
                        ->load('getParent', array(':parent' => $data->__id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
    
                    // se ci sono discendenti
                    if ($it->count() > 0) {
                        // crea un job per aggiornare i nodi discendenti perchè la denominazion è cambiata
                        $jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
                        $jobFactory->createJob(
                            'archivi.services.DenominazioneService',
                            array(
                                'id' => $data->__id
                            ),
                            'Cambio denominazione',
                            'BACKGROUND'
                        );
                    }
                }
            }

            $result = $this->proxy->saveContent($data, __Config::get('glizycms.content.history'), $isDraft === true);
        }

        if ($result['__id']) {
            return array('set' => $result);
        } else {
            return array('errors' => $result);
        }
    }

    public function benchEnd($str)
    {
        if ($this->profiling === true) {
            $x = $this->bench[$str]['start'];
            $this->bench[$str]['time'] = $x ? round((microtime(true) - $x) * 1000, 3) : 0;
        }
    }

    /**
     * @param $data
     * @param $res
     * @param $isDraft
     * @return array
     */
    protected function updateIdentificativi($data, $res, $isDraft)
    {
        $data->__id = $res['set']['__id'];

        if (!$data->identificativo || !$data->codiceIdentificativoSistema) {
            $data->identificativo = $data->acronimoSistema . " " . self::$sigle[$data->__model] . " " . $data->__id;
            $data->codiceIdentificativoSistema = $data->__id;
            $res = $this->saveObject($data, $isDraft === true);
        }

        return $res;
    }

    public function getParentsArray($parent, &$parentsPath)
    {
        if ($parent->id) {
            $ar = __ObjectFactory::createModel('archivi.models.Model');
            $ar->load($parent->id, 'PUBLISHED_DRAFT');
            $this->getParentsArray((object)$ar->parent, $parentsPath);
            $parentsPath[$ar->getId()] = $ar->denominazione;
        }
    }

    /**
     * @param $data stdClass
     * @param bool $mapFE
     */
    public function sendDataToSolr($data, $mapFE = false)
    {
        // presenza del digitale o meno
        $data->_hasDigital = $this->hasImageHelper->hasImage($data, 'archive') ? 1 : 0;
        
        if ($data->parent) {
            $parentsPath = array();
            $this->getParentsArray($data->parent, $parentsPath);
            $data->_parentsIds = array_keys($parentsPath);
            $data->_parents = array_values($parentsPath);
            $data->_complessoAppartenenza = $data->_parents[0];
        }

        parent::sendDataToSolr($data);

        if ($mapFE) {
            $options = $this->getSolrOption();
            $this->arcFEHelper->mappingFE($data, $options['queue'] ? 'queue' : 'commit');
        }
    }

    public function reindexDescendants($id, $mapFE = false)
    {
        $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
            ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

        foreach ($it as $ar) {
            $this->reindexAr($ar, $mapFe);
            $this->reindexDescendants($ar->getId(), $mapFe);
        }
    }

    public function reindexAr($ar, $mapFE = false)
    {
        $data = $ar->getRawData();
        $data->__model = $data->document_type;
        $data->__id = $data->document_id;
        $data->instituteKey = $ar->instituteKey;
        $this->appendDocumentToData($data);
        $this->sendDataToSolr($data, $mapFE);
    }

    /**
     * Esegue quel che avrebbe eseguito la archivi_controllers_ajax_SaveDraft::execute()
     * @param $data stdClass
     * @param bool $invertRelation (Default TRUE) serve per chiamare il proxy di inversione delle relazioni
     * @return array|null
     */
    public function saveDraft($data, $invertRelation = true)
    {
        $isDraft = true;
        $data->isValid = 0;
        $data->root = (!$data->parent) ? 'true' : 'false';

        return $this->saveProcedure($data, $invertRelation, $isDraft);
    }

    public function delete($id, $recurse = false, $control = false, $feOnly = false)
    {
        $this->stack[] = $id;
        $ret = array($id);
        $it =
            $recurse ?
                org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
                ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')))
                :
                array();

        foreach ($it as $ar) {
            if (!in_array($ar->getId(), $this->stack)) {
                $ret = array_merge($ret, $this->delete($ar->getId(),$recurse,false,$feOnly));
            }
        }

        $this->deleteItem($id, $control, $feOnly);
        //echo "Deleted item $id\n<br>\n";
        array_pop($this->stack);
        return $ret;
    }

    private function deleteItem($id, $control = false, $feOnly)
    {
        if ($control === true && $this->archiviControl($id)) {
            return false;
        } else {
            if(!$feOnly)
            {
              $evt = array('type' => 'deleteRecord', 'data' => $id);
              $this->dispatchEvent($evt);
            }
            $evt2 = array('type' => 'deleteRecord', 'data' => array('id' => $id, 'option' => array('url' => __Config::get('metafad.solr.metaindice.url'))));
            $this->dispatchEvent($evt2);

            $evt3 = array('type' => 'deleteRecord', 'data' => array('id' => $id, 'option' => array('url' => __Config::get('metafad.solr.archive.url'))));
            $this->dispatchEvent($evt3);

            $evt4 = array('type' => 'deleteRecord', 'data' => array('id' => $id, 'option' => array('url' => __Config::get('metafad.solr.metaindiceaut.url'))));
            $this->dispatchEvent($evt4);

            $evt5 = array('type' => 'deleteRecord', 'data' => array('id' => $id, 'option' => array('url' => __Config::get('metafad.solr.archiveaut.url'))));
            $this->dispatchEvent($evt5);

            if(!$feOnly)
            {
              $this->proxy->delete($id, 'archivi.models.Model');
            }

            return true;
        }
    }

    public function archiviControl($archiviId)
    {
        $it = __ObjectFactory::createModelIterator('archivi.models.Model');

        $found = false;
        foreach ($it as $ar) {
            $parentId = $ar->getRawData()->parentId;
            if ($parentId && $parentId == $archiviId) {
                $found = true;
            }
        }

        return $found;
    }

    public function getLinkObjectsByExternalId($extid, $model = 'archivi.models.Model', $first = true)
    {
        if (!$extid) {
            return array();
        }
        $it = __ObjectFactory::createModelIterator($model)
            ->load('getByIndexedText', array(':textName' => "externalID", ":textVal" => $extid, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

        $ret = array();
        /**
         * @var $ar org_glizy_dataAccessDoctrine_ActiveRecord
         */
        foreach ($it as $ar) {
            $ret[] = (object)array("id" => $ar->getId(), "text" => $ar->_denominazione);
            if ($first) break;
        }

        return $ret;
    }

    /**
     * Tenta un accesso alla proprietà desiderata. L'array indica una cosa come "obj->ar[0]->ar[1]->...->ar[n]"
     * @param $obj
     * @param $properties
     * @param null $default
     * @return mixed|null
     */
    private function softAccess($obj, $properties, $default = null){
        $obj = json_decode(json_encode($obj));
        if (!is_array($properties) || !is_object($obj)){
            return null;
        }

        $i = -1;
        $n = count($properties);
        $got = $obj;
        while($got !== null && ++$i < $n){
            $propname = $properties[$i];
            if (!is_string($propname) || (!is_object($got) && !is_array($got))){
                $got = null;
            } else if (is_object($got) && property_exists($got, $propname)) {
                $got = $got->$propname;
            } else if (is_array($got) && count($got) && is_object($got[0]) && property_exists($got[0], $propname)) {
                //TODO uno solo?
                $got = $got[0]->$propname;
            } else {
                $got = null;
            }
        }

        return $got === null ? $default : $got;
    }

    public function getLinkObjectById($id, $model = 'archivi.models.Model')
    {
        $ar = __ObjectFactory::createModelIterator($model)->where("document_id", $id)->first();

        if ($ar) {
            return (object)array("id" => $id, "text" => $ar->_denominazione);
        } else {
            return null;
        }
    }

    /**
     * Viene restituito un link (array con id e text) della mini-authority trovata/salvata
     * @param $data
     * @param $model
     * @param string $firstField
     * @param string $secondField
     * @return mixed
     */
    public function addOrGetMiniAuthorityLink($data, $model, $firstField = 'intestazione', $secondField = 'externalID'){
        $model = $model ?: $data->__model;
        $firstField = $firstField ?: 'intestazione';
        $secondField = $secondField ?: 'externalID';
        $it = __ObjectFactory::createModelIterator($model);
        $result = array();

        if ($data->$firstField) {
            $it->load('getByIndexedText', array(':textName' => $firstField, ":textVal" => $data->$firstField, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
        }

        if ((!$data->$firstField || $it->count() == 0) && $data->$secondField){
            unset($it);
            $it = __ObjectFactory::createModelIterator($model)
                ->load('getByIndexedText', array(':textName' => $secondField, ":textVal" => $data->$secondField, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
        }

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->intestazione
            );
            break;
        }

        if (count($result) == 0){
            $data->__model = $model;
            $data->externalID = $data->externalID ?: "";
            $ret = $this->save($data);
            $result = array(array('id' => $ret['set']['__id'], 'text' => $data->intestazione));
        }

        return current($result);
    }

    /**
     * @param $data
     * @return mixed|null|string
     */
    private function extractCronologiaFromStdClass($data)
    {
        $chronos = "";
        $attempts = array(
            array("cronologia", "estremoCronologicoTestuale"),
            array("cronologiaEnte", "estremoCronologicoTestuale"),
            array("cronologiaPersona", "estremoCronologicoTestuale"),
            array("cronologiaFamiglia", "estremoCronologicoTestuale"),
            array("cronologiaRedazione", "estremoCronologicoTestuale"),
            array("annoDiEdizione")
        );

        foreach ($attempts as $attempt) {
            $chronos = $this->softAccess($data, $attempt, $chronos) ?: $chronos;
        }

        return $chronos;
    }

    /**
     * @param $data
     * @return string
     */
    private function extractDenominazioneFromStdClass($data)
    {
        $name = "";
        $surname = "";
        $attempts = array(
            array("denominazione"),
            array("titoloAttribuito"),
            array("titolo"),
            array("ente_famiglia_denominazione", "entitaDenominazione"),
            array("persona_denominazione", "entitaDenominazione"),
            array("titoloNormalizzato"),
            array("titoloLibroORivista")
        );
        foreach ($attempts as $attempt) {
            $surname = $this->softAccess($data, $attempt, $surname) ?: $surname;
        }

        $attempts = array(
            array("persona_denominazione", "persona_nome"),
        );
        foreach ($attempts as $attempt) {
            $name = $this->softAccess($data, $attempt, $name) ?: $name;
        }

        return implode(", ", array_filter(array($surname, $name)));
    }

    /**
     * @param $data
     * @return mixed|null
     */
    private function extractIdentificativoFromStdClass($data)
    {
        $title = "";
        return $this->softAccess($data, array("identificativo"), $title);
    }

    /**
     * @param $data
     * @return string
     */
    public function extractTitleFromStdClass($data)
    {
        $title = array();
        $title[] = $this->extractIdentificativoFromStdClass($data);
        $title[] = $this->extractDenominazioneFromStdClass($data);
        $title[] = $this->extractCronologiaFromStdClass($data);

        return count(array_filter($title)) > 0 ? implode(' || ', array_map(function($a){return $a ?: " - ";}, $title)) : ($this->softAccess($data, array("intestazione")) . "");
    }

    private function isConservatore($entity)
    {
        $conservatoreFields = array(
            "cenniStoriciIstituzionali",
            "sog_cons_patrimonio",
            "sog_cons_note",
            "sog_cons_mail",
            "sog_cons_pec",
            "sog_cons_url",
            "sog_cons_telefono",
            "sog_cons_fax",
            "sog_cons_sedi",
            "condizioniAccesso",
            "complessiArchivisticiConservatore",
            "riferimentiBibliograficiConvervatore",
            "fontiArchivisticheConvervatore",
            "riferimentiWebConvervatore",
            "linguaDescrizioneRecordConvervatore",
            "compilazioneConvervatore",
            "osservazioniConvervatore"
        );

        return $this->hasAnyNotEmptyField($entity, $conservatoreFields);
    }

    private function isProduttore($entity)
    {
        $produttoreFields = array(
            "storiaBiografiaStrutturaAmministrativa",
            "complessiArchivisticiProduttore",
            "soggettiProduttori",
            "riferimentiBibliograficiProduttore",
            "fontiArchivisticheProduttore",
            "riferimentiWebProduttore"
        );

        return $this->hasAnyNotEmptyField($entity, $produttoreFields);
    }

    public static function buildProdConsArray($isProd = false, $isCons = false)
    {
        $inferred = array();

        if ($isProd) {
            $inferred[] = "Produttore";
        }

        if ($isCons) {
            $inferred[] = "Conservatore";
        }

        return $inferred;
    }

    private function buildProdConsField($data)
    {
        if ($data->__model != 'archivi.models.ProduttoreConservatore') {
            return;
        }

        $forced = $data->isForceProdCons;
        $forced = is_array($forced) ? $forced : array();

        $inferred = self::buildProdConsArray(
            $this->isProduttore($data) || in_array("SoggettoProduttore", $forced),
            $this->isConservatore($data) || in_array("SoggettoConservatore", $forced)
        );

        $data->isProdCons = $inferred;
    }

    /**
     * @param $entity
     * @param $conservatoreFields
     * @return bool
     */
    private function hasAnyNotEmptyField($entity, $conservatoreFields)
    {
        foreach ($conservatoreFields as $field) {
            if (metafad_common_helpers_ImporterCommons::purgeEmpties($entity->$field)) {
                return true;
            }
        }

        return false;
    }
}
