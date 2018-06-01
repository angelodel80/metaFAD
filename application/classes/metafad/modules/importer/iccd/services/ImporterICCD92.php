<?php

class metafad_modules_importer_iccd_services_ImporterICCD92 extends GlizyObject
{

    var $version;
    var $modelPath;
    var $records;
    var $immftan;
    var $documents;
    var $tempDocuments;
    var $duplicates;
    var $dir;
    var $trc;
    var $currentScheda;
    var $iccdFormProxy;
    /** @var metafad_gestioneDati_boards_models_proxy_ICCDProxy*/
    var $iccdProxy;
    var $uniqueIccdIdProxy;
    var $logger;
    /** @var int */
    private $startProgress = 0;
    private $lastTimestamp = 0;

    /**
     * @return int
     */
    public function getStartProgress()
    {
        return $this->startProgress;
    }

    /**
     * @param int $startProgress
     */
    public function setStartProgress($startProgress)
    {
        $this->startProgress = $startProgress;
    }

    /** var metafad_teca_MAG_services_ImportMediaInterface */
    protected $dam;

    private function logMsg($msg, $lvl = GLZ_LOG_DEBUG){
        if ($this->lastTimestamp == 0){
            $this->lastTimestamp = round(microtime(true)*1000, 3);
            $delta = 0;
        } else {
            $curTimestamp = round(microtime(true)*1000, 3);
            $delta = round($curTimestamp - $this->lastTimestamp, 3);
            $this->lastTimestamp = $curTimestamp;
        }
        if ($this->logger){
            $this->logger->log("(Time delta: {$delta}ms) $msg", $lvl);
        }
    }

    public function __construct($trcInstance, $immftanInstance, $iccdProxy, metafad_teca_DAM_services_ImportMediaInterface $dam, $modelPath, org_glizy_log_LogBase $logger = null, $importDir = '')
    {
        $this->trc = $trcInstance;
        $this->immftan = $immftanInstance;
        $this->iccdProxy = $iccdProxy;
        $this->modelPath = $modelPath;
        $this->version = $this->trc->version;
        $this->iccdFormProxy = __ObjectFactory::createObject('metafad.modules.iccd.models.proxy.IccdFormProxy');
        $this->uniqueIccdIdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.UniqueIccdIdProxy');
        $this->dam = $dam;
        $this->logger = $logger;
        $this->dir = $importDir ?: '';
    }


    /**
     * @param array $options opzioni (per esempio 'forceDraft' per forzare il salvataggio in draft)
     * @return bool
     */
    public function import($options = array())
    {
        $forceDraft = key_exists("forceDraft", $options ?: array()) ? $options['forceDraft'] : false;
        $overwriteScheda = key_exists("overwriteScheda", $options ?: array()) ? $options['overwriteScheda'] : false;
        $overwriteAuthority = key_exists("overwriteAuthority", $options ?: array()) ? $options['overwriteAuthority'] : false;

        $progress = $this->startProgress;
        $remaining = 100 - $progress;

        $this->records = $this->trc->records;
        $this->iccdProxy->setQueueSize(50);

        $delta = $remaining / (count($this->records) ?: 1);

        if (!empty($this->records)) {
            foreach ($this->records as $data) {
                $this->currentScheda = $data;

                $id = $this->getIdScheda($data);

                if ($this->immftan != NULL) {
                    $this->logMsg("Scheda $id: Avvio importazione/associazione immagini", GLZ_LOG_INFO);
                    $this->getAndSaveImage($data);
                    $this->logMsg("Scheda $id: Importazione/associazione immagini completata");
                    $this->progressEvent($progress + $delta*0.75, "Scheda $id: Immagini salvate ed associate correttamente");
                } else {
                    $this->progressEvent($progress + $delta*0.75, "Scheda $id: Nessuna immagine da associare");
                }


                $this->logMsg("Scheda $id: Ricerca e associazione AUT/BIB in corso", GLZ_LOG_INFO);
                $a = $this->saveRefAUT($data, $forceDraft, $overwriteAuthority);
                $b = $this->saveRefBIB($data, $forceDraft, $overwriteAuthority);
                $autBibMsg = ($a || $b) ? "Associazione AUT/BIB completata." : "Nessuna associazione AUT/BIB trovata.";
                $this->logMsg("Scheda $id: " . $autBibMsg);
                $this->progressEvent($progress + $delta*0.83, "Scheda $id: " . $autBibMsg);

                $this->logMsg("Scheda $id: Salvataggio scheda in corso...", GLZ_LOG_INFO);
                $data->__id = 0;
                $data->__model = $this->modelPath;
                $result = $this->addContent($data, false, $forceDraft, $overwriteScheda);
                $this->logMsg("Scheda $id: Salvata con ID={$result['set']['__id']}");
                $this->progressEvent($progress += $delta, "Scheda $id salvata correttamente con ID = {$result['set']['__id']}");
            }

            //Se ci sono duplicati, lo segnalo
            if (!empty($this->duplicates)) {
                $this->logMsg('---------------');
                $this->logMsg('Duplicati:');
                foreach ($this->duplicates as $k => $v)
                    if ($v > 1) {
                        $this->logMsg("($v) $k");
                    }
            }

            $this->documents = array();
            $this->tempDocuments = array();
            $this->records = array();

            $this->iccdProxy->commit();

            return true;
        }
        return false;
    }


    private function getAndSaveImage($data)
    {
        $images = $this->immftan->getImages($data);
        if (!empty($images))
            foreach ($images as $i => $image) {
                if ($image != null) {
                    $id = $this->getIdScheda($data);
                    $this->logMsg("Scheda $id: tentativo di salvataggio dell'immagine n°$i ($image)");
                    $img = $this->saveImageInDAM($image);
                    if (!$img) {
                        $this->logMsg("Scheda $id: Salvataggio immagine n°$i ($image) non riuscito");
                    } else {
                        $this->logMsg("Scheda $id: Salvataggio dell'immagine n°$i ($image) riuscito");
                    }
                    $data->FTA[$i]->{'FTA-image'} = $img;
                }
            }
    }


    private function saveRefAUT($data, $forceDraft = false, $overwriteAut = true)
    {
        $count = 0;
        //Salvo i riferimenti relativi ad AUT
        $AUT = $data->AUT;
        if (!empty($AUT)) {
            $autIDs = $this->getAUTIDs($AUT, $forceDraft, $data, $overwriteAut);

            $count = count($AUT);
            for ($i = 0; $i < $count; $i++)
                $data->AUT[$i]->__AUT = $autIDs[$i]['__AUT'];
        } else {
            $AUT = $data->AU[0]->AUT;
            if (!empty($AUT)) {
                $autIDs = $this->getAUTIDs($AUT, $forceDraft, $data, $overwriteAut);

                $count = count($autIDs);
                for ($i = 0; $i < $count; $i++)
                    $data->AU[0]->AUT[$i]->__AUT = $autIDs[$i]['__AUT'];
            }
        }
        return $count;
    }


    private function saveRefBIB($data, $forceDraft = false, $overwriteAut = true)
    {
        $count = 0;
        //Salvo i riferimenti relativi a BIB
        $BIB = $data->BIB;
        if (!empty($BIB)) {
            $bibIDs = $this->getBIBIDs($BIB, $forceDraft, $data, $overwriteAut);

            $count = count($BIB);
            for ($i = 0; $i < $count; $i++)
                $data->BIB[$i]->__BIB = $bibIDs[$i]['__BIB'];
        } else {
            $BIB = $data->DO[0]->BIB;
            if (!empty($BIB)) {
                $bibIDs = $this->getBIBIDs($BIB, $forceDraft, $data, $overwriteAut);

                $count = count($bibIDs);
                for ($i = 0; $i < $count; $i++)
                    $data->DO[0]->BIB[$i]->__BIB = $bibIDs[$i]['__BIB'];
            }
        }
        return $count;
    }


    /**
     * Salvataggio immagini nel DAM
     *
     * @param string $image Percorso e nome dell'immagine
     *
     * @return integer|null
     */
    private function saveImageInDAM($image)
    {
        $filePath = $this->dir . $image;
        if ($image != '') {
            $media = new stdClass();
            $media->title = pathinfo($filePath, PATHINFO_BASENAME);
            $media->filename = $filePath;

            $mediaData = array();
            $mediaData['addMedias'][] = array(
                'MainData' => $media,
                'bytestream' => realpath($filePath)
            );
            $mediaData = json_encode($mediaData);
            $mediaExists = $this->dam->mediaExists($filePath);

            if (!$mediaExists['response']) {
                $res = $this->dam->insertMedia($mediaData, 'media');
            }

            if (!empty($res)) {
                $result = $this->dam->getJSON($res->ids[0], $media->title);
            } else if ($mediaExists['ids']) {
                $result = $this->dam->getJSON($mediaExists['ids'][0], $media->title);
            } else {
                $this->logMsg("$filePath non è stato salvato correttamente (path nel FS " . (realpath($filePath) ?: "non esistente!") . ")");
                $result = NULL;
            }
        }

        return !empty($result) ? $result : NULL;
    }


    /**
     * Salvataggio tramite proxy delle schede
     *
     * @param object $obj Oggetto contenente i dati della scheda da salvare
     *
     * @param bool $autbib Se la scheda è AUT/BIB
     * @param bool $forceDraft Se la scheda dev'essere forzatamente messa in draft
     * @param bool $overwrite Se la scheda deve sovrascrivere quella già presente (nel caso in cui $obj->__id > 0)
     * @return array risultato della save
     */
    private function addContent($obj, $autbib = false, $forceDraft = false, $overwrite = true)
    {
        $id = $this->getIdScheda($obj);

        if (!json_encode($obj) && json_last_error() == JSON_ERROR_UTF8) {
            $this->utf8_encode_deep($obj);
        }

        //controllo se la scheda in questione esiste già
        $this->logMsg("Scheda $id: Creazione ID univoco ICCD in corso");
        $uniqueIccdId = $this->uniqueIccdIdProxy->createUniqueIccdId($obj);
        $obj = $this->uniqueIccdIdProxy->checkUnique($obj, $uniqueIccdId, true);
        $this->logMsg("Scheda $id: Creazione ID univoco ICCD terminato");


        if ($obj->__id != 0 && !$overwrite) {
            return array('set' => array('__id' => $obj->__id));
        }

        if (is_array($this->duplicates) && ($obj->__id != 0 or array_key_exists($uniqueIccdId, $this->duplicates))){
            $this->duplicates[$uniqueIccdId]++;
        } else {
            $this->duplicates[$uniqueIccdId] = 1;
        }

        if (!$autbib) {
            $this->rebuildRSEandROZ($obj, $id);
        }

        $this->logMsg("Scheda $id: perfezionamento scheda (eliminazione array vuoti) in corso");
        $purged = metafad_common_helpers_ImporterCommons::purgeEmpties($obj);
        $this->logMsg("Scheda $id: salvataggio scheda in corso");
        $result = $forceDraft ? $this->iccdProxy->saveDraft($purged) : $this->iccdProxy->save($purged, true);

        return $result;
    }

    private function getAUTIDs($AUTs, $forceDraft = false, $linkedData = null, $overwrite = true)
    {
        $modelName = 'AUT' . $this->version . '.models.Model';

        $ids = array();

        foreach ($AUTs as $aut) {
            if ($linkedData && $linkedData->ESC){
                $aut->ESC = $linkedData->ESC;
            }

            $autCopy = $aut;

            if ((is_object($aut) || is_array($aut)) && !empty($aut)) {
                $autCopy->__model = $modelName;
                $autCopy->TSK = "AUT";

                if ($autCopy->AUTH) {
                    $uniqueIccdId = $this->uniqueIccdIdProxy->createUniqueIccdId($autCopy);
                    $it = __ObjectFactory::createModelIterator($modelName)
                        ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
                        ->where('uniqueIccdId', $uniqueIccdId);
                    $arAUT = $it->first();
                    $autCopy->__id = $arAUT ? $arAUT->getId() : 0;
                } else {
                    $autCopy->__id = 0;
                }

                $result = $this->addContent($autCopy, false, $forceDraft, $overwrite);

                $ids[] = array('__AUT' => array('id' => $result['set']['__id'], 'text' => $autCopy->AUTN . ' - ' . $autCopy->AUTH));
            }
        }

        return $ids;
    }


    private function getBIBIDs($BIBs, $forceDraft = false, $linkedData = null, $overwrite = true)
    {
        $modelName = 'BIB' . $this->version . '.models.Model';

        $ids = array();

        $fieldName = ($this->version == '400' ? 'BIBM' : 'BIBA');

        foreach ($BIBs as $bib) {
            if ($linkedData && $linkedData->ESC){
                $bib->ESC = $linkedData->ESC;
            }

            $bibCopy = $bib;

            if ((is_object($bib) || is_array($bib)) && !empty($bib)) {
                $bibCopy->__model = $modelName;
                $bibCopy->TSK = "BIB";

                if ($bibCopy->BIBH) {
                    $uniqueIccdId = $this->uniqueIccdIdProxy->createUniqueIccdId($bibCopy);
                    $it = __ObjectFactory::createModelIterator($modelName)
                        ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
                        ->where('uniqueIccdId', $uniqueIccdId);
                    $arBIB = $it->first();
                    $bibCopy->__id = $arBIB ? $arBIB->getId() : 0;
                } else {
                    $bibCopy->__id = 0;
                }

                $result = $this->addContent($bibCopy, false, $forceDraft, $overwrite);

                $ids[] = array('__BIB' => array('id' => $result['set']['__id'], 'text' => $bibCopy->$fieldName . ' - ' . $bibCopy->BIBH));
            }
        }

        return $ids;
    }


    private function ICCDfind($model, $uniqueIccdId)
    {
        if (!$uniqueIccdId) {
            $id = $this->getIdScheda($this->currentScheda);
            $this->logMsg("Scheda $id: Ricerca $model per uniqueIccdId interrotta, $uniqueIccdId mancante");
            return null;
        }
        $ar = __ObjectFactory::createModelIterator($model)
            ->where("uniqueIccdId", $uniqueIccdId)
            ->first();

        if ($ar != null){
            return (object)array(
                "id" => $ar->getId(),
                "text" => $uniqueIccdId
            );
        }

        return null;
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

    /**
     * @param $progress
     * @param $message
     */
    protected function progressEvent($progress, $message)
    {
        $evt = array("type" => "mainRunnerProgress", "data" => array("progress" => $progress, "message" => $message));
        $this->dispatchEvent($evt);
    }

    /**
     * @param $data
     * @return string
     */
    protected function getIdScheda($data)
    {
        @$RVEL = ($data->RV[0]->RVE[0]->RVEL != '') ? '-' . $data->RV[0]->RVE[0]->RVEL : '';
        @$id = $data->NCTR . $data->NCTN . $data->NCTS . $RVEL;
        $id = $id ?: "";
        if (!$id && $data->__model){
            $model = $data->__model;
            $type = str_replace(array("Scheda",".models.Model"),array("",""),$model);
            $type = preg_replace('/[0-9]+/', '', $type);
            $field = $type.'H';
            $id = (__Config::get("metafad.iccd.uniqueIccdId.useESC") ? $data->ESC : "").$data->$field;
        }

        return $id;
    }

    /**
     * @param $obj
     * @param $id
     */
    private function rebuildRSEandROZ($obj, $id)
    {
        $rv = $obj->RV[0];

        if ($rv->RSE) {
            $this->logMsg("Scheda $id: Legami RSE in corso");
            foreach ($rv->RSE as $i => $rse) {
                $this->logMsg("Scheda $id: Legame $i RSE in corso di elaborazione");
                $objtemp = $this->ICCDfind($obj->__model, $rse->RSEC);
                $rse->RSEC = $objtemp ?: (object)array("id" => 0, "text" => $rse->RSEC);
                $idx = $objtemp ? $objtemp->id : null;
                $this->logMsg("Scheda $id: Legame $i RSE terminata " . ($idx ? "id $idx " : "nessun id") . " trovato.");
            }
        }

        if ($rv->ROZ) {
            $this->logMsg("Scheda $id: Legami ROZ in corso");
            foreach ($rv->ROZ as $i => $roz) {
                $this->logMsg("Scheda $id: Legame $i ROZ in corso di elaborazione");
                $objtemp = $this->ICCDfind($obj->__model, $roz->{'ROZ-element'});
                $roz->{'ROZ-element'} = $objtemp ?: (object)array("id" => 0, "text" => $roz->{'ROZ-element'});
                $idx = $objtemp ? $objtemp->id : null;
                $this->logMsg("Scheda $id: Legame $i ROZ terminata " . ($idx ? "id $idx " : "nessun id") . " trovato.");
            }
        }
    }

    public static function buildPath($path, $toPrepend = "/")
    {
        if (strpos("/", $path) === 0) {
            return $path;
        }

        return preg_replace('/\/+/', "/", $toPrepend.$path);
    }
}
