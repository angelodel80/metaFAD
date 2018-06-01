<?php

/**
 * Questo è un toolset creato per avere utilità comuni per gli importatori/esportatori.
 * La lista di funzionalità è ancora incompleta.
 * Queste utilità sono servite ad-hoc per risolvere delle POLODEBUG
 * User: marco
 * Date: 02/01/17
 * Time: 12.50
 */
class metafad_common_helpers_ImporterCommons
{
    private static $days = array(
        "1" => "31",
        "01" => "31",
        "2" => "28",
        "02" => "28",
        "3" => "31",
        "03" => "31",
        "4" => "30",
        "04" => "30",
        "5" => "31",
        "05" => "31",
        "6" => "30",
        "06" => "30",
        "7" => "31",
        "07" => "31",
        "8" => "31",
        "08" => "31",
        "9" => "30",
        "09" => "30",
        "10" => "31",
        "11" => "30",
        "12" => "31"
    );

    /**
     * Aggiunge e restituisce il valore dentro il dizionario scelto.
     * @param $dictionaryCode string Codice del dizionario in cui aggiungere il valore
     * @param $termCode string Codice del termine da inserire
     * @param $termLabel string|null Valore del termine da inserire (se null o omesso, il valore coinciderà con il codice)
     * @param $termLevel int Livello del termine, per i vocabolari gerarchici
     * @param $parentKey int Identificatore del record del termine padre, per i vocabolari gerarchici
     * @return string Il valore del termine inserito
     * @throws Exception Se il dizionario richiesto non esiste
     */
    public static function addOrGetTerm($dictionaryCode, $termCode, $termLabel = null, $termLevel = 1, $parentKey = 0)
    {
        $termCode = trim($termCode . "");
        $termLevel = !is_numeric($termLevel) || $termLevel <= 0 ? 1 : $termLevel;
        $termLabel = strlen($termLabel . "") <= 0 ? $termCode : $termLabel;

        if (strlen($termCode) <= 0 || strlen($termLabel . "") <= 0) {
            return "";
        }

        $thesaurus = __ObjectFactory::createModel('metafad.modules.thesaurus.models.Thesaurus');
        $thesaurusDetail = __ObjectFactory::createModel('metafad.modules.thesaurus.models.ThesaurusDetails');

        $existTerm = $thesaurusDetail->find(array('thesaurusdetails_key' => $termCode, 'thesaurus_code' => $dictionaryCode));
        $result = $thesaurus->find(array('thesaurus_code' => $dictionaryCode));

        if ($result && !$existTerm) {
            $arDetails = __ObjectFactory::createModel('metafad.modules.thesaurus.models.Details');
            $arDetails->thesaurusdetails_FK_thesaurus_id = $thesaurus->getId();
            $arDetails->thesaurusdetails_level = $termLevel;
            $arDetails->thesaurusdetails_key = $termCode;
            $arDetails->thesaurusdetails_value = $termLabel;
            $arDetails->thesaurusdetails_parent = $parentKey;
            $arDetails->save();
            $arDetails->emptyRecord();
        } else if (!$result) {
            throw new Exception("Durante l'inserimento di un nuovo termine ('$termCode') nel dizionario: dizionario '$dictionaryCode' inesistente");
        }

        return $termLabel;
    }

    /**
     * Restituisce una versione normalizzata della data indicata
     * @param integer|string $y Anno numerico
     * @param integer|string $m Mese numerico
     * @param integer|string $d Giorno numerico
     * @return string
     */
    public static function normalizeDate($y = null, $m = null, $d = null)
    {
        if (strlen($y . "") == 0 || intval($y . "") <= 0) {
            return "";
        } else if (strlen($m . "") == 0 || intval($m . "") <= 0) {
            return "{$y}0101-{$y}1231";
        } else if (strlen($d . "") == 0 || intval($d . "") <= 0) {
            $m = self::addBeginningChars($m);
            return "{$y}{$m}01-{$y}{$m}" . self::$days[$m];
        } else {
            $m = self::addBeginningChars($m);
            $d = self::addBeginningChars($d);
            return "{$y}{$m}{$d}";
        }
    }

    /**
     * Aggiunge in testa alla stringa data (utile per fare padding dei numeri)
     * @param $number
     * @param string $char
     * @param int $length
     * @return string
     */
    private static function addBeginningChars($number, $char = "0", $length = 2)
    {
        $remaining = $length - strlen($number . "");
        if ($remaining <= 0 || $number === null) {
            return $number;
        } else {
            return str_repeat($char . "", $remaining) . $number;
        }
    }

    /**
     * Restituisce la data formattata secondo A/M/G
     * @param integer|string $y Anno numerico
     * @param integer|string $m Mese numerico
     * @param integer|string $d Giorno numerico
     * @param string $separator Separatore per concatenare la data (default '/')
     * @return string
     */
    public static function formatDateYMD($y = null, $m = null, $d = null, $separator = "/")
    {
        $m = strlen($y . "") ? self::addBeginningChars($m) : null;
        $d = $m ? self::addBeginningChars($d) : null;

        $arr = array_filter(array($y, $m, $d), function ($a) {
            return strlen($a . "") > 0 && intval($a) > 0;
        });

        return implode($separator, $arr);
    }

    /**
     * Restituisce la data formattata secondo G/M/A
     * @param integer|string $y Anno numerico
     * @param integer|string $m Mese numerico
     * @param integer|string $d Giorno numerico
     * @param string $separator Separatore per concatenare la data (default '/')
     * @return string
     */
    public static function formatDateDMY($y = null, $m = null, $d = null, $separator = "/")
    {
        $m = strlen($y . "") ? self::addBeginningChars($m) : null;
        $d = $m ? self::addBeginningChars($d) : null;

        $arr = array_filter(array($d, $m, $y), function ($a) {
            return $a !== null;
        });

        return implode($separator, $arr);
    }

    /**
     * Mantiene solo le stringhe che rappresentano date e le concatena con " - " ed eventualmente con la qualifica specificata.
     * (Si veda il punto 5 e 6 della POLODEBUG-481 lato BE (omg Cerullo y u do dis?))
     * @param $remoto
     * @param $recente
     * @param $qualifica
     * @return string
     */
    public static function getCronologicoTestuale($remoto, $recente, $qualifica = "")
    {
        $estremoTxt = implode(" - ", array_filter(array($remoto, $recente), array("metafad_common_helpers_ImporterCommons", "canBeDate")));

        $qualifica = $qualifica ?: "";
        $qualifica = trim($qualifica);
        if (!$qualifica || !$estremoTxt){
            return $estremoTxt;
        }

        return strpos("con ", strtolower($qualifica)) === 0 ? "$qualifica $estremoTxt" : "$estremoTxt ($qualifica)";
    }

    private static function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Elimina i valori vuoti presenti nell'argomento dato in input.
     * <br>
     * Il funzionamento effettivo di questo metodo è quello di mantenere la struttura della stdClass mantenuta
     * e di svuotare semplicemente gli array che contengono valori vuoti. Questo significa che il primo livello della stdClass
     * non perderà campi.
     * <br>
     * La definizione di vuoto è data da <b>notEmpty</b> di questa classe.
     * @param $purgeMe mixed valore da svuotare dai campi (o elementi) vuoti (se è un valore primitivo, non ha campi)
     * @return array|mixed|string Valore con i campi vuoti potati
     */
    public static function purgeEmpties($purgeMe)
    {
        $ret = $purgeMe;
        if (is_array($purgeMe) && self::isAssoc($purgeMe)){
            $ret = (object) $purgeMe;
        }

        if ($ret == null){
            return $ret;
        }

        if (is_array($ret)) {
            return array_filter(array_map(function ($a) {
                return metafad_common_helpers_ImporterCommons::purgeEmpties($a);
            }, $ret), function ($a) {
                return metafad_common_helpers_ImporterCommons::notEmpty($a);
            });
        } else if (is_object($ret)) {
            foreach ($ret as $k => $v) {
                $ret->$k = self::purgeEmpties($ret->$k);
            }
        }
        return $ret;
    }

    /**
     * Controlla se un oggetto è considerabile "non vuoto".
     * <br>
     * Un valore primitivo è vuoto se è diverso da null o stringa vuota (false è ammissibile)
     * <br>
     * Un array è vuoto se non contiene elementi non vuoti
     * <br>
     * Una stdClass è vuota se non ha nessun campo che contiene valori non vuoti
     * <br>
     * Esempio di primitivi vuoti: null, ""
     * <br>
     * Esempio di array vuoti: [], ["", ""], ["", {}, {"a": "", "n": [""]}]
     * <br>
     * Esempio di stdClass vuote: {}, {"a": []}, {"a": {"b": ["", ""]}, "c": ["", {}]}
     *
     *
     * @param $field mixed campo da controllare
     * @return bool Se il campo NON è vuoto
     */
    public static function notEmpty($field)
    {
        if (is_object($field)) {
            $count = 0;
            foreach ($field as $k => $v) {
                $count += (self::notEmpty($field->$k) ? 1 : 0);
            }
            return $count > 0;
        } else if (is_array($field)) {
            return count(array_filter($field, function ($a) {
                return metafad_common_helpers_ImporterCommons::notEmpty($a);
            })) > 0;
        } else {
            return ($field === false || strlen($field . "") > 0);
        }
    }

    /**
     * @param $intestazione
     * @return stdClass
     */
    public static function inferEnteFromIntestazione($intestazione)
    {
        $data = new stdClass();
        $main = explode("<", str_replace(">", "", $intestazione)); //TODO: scandalosamente naive
        $infos = count($main) > 1 ? array_map('trim', explode(";", trim($main[1]))) : array();

        $dateAttivita = array_filter($infos, function ($a) {
            return metafad_common_helpers_ImporterCommons::canBeDate($a);
        });

        $data->denominazioneEnte = count($main) > 0 ? trim($main[0]) : "";
        $data->dateAttivita = implode(" ; ", $dateAttivita);
        $data->intestazione = $intestazione;
        $data->__model = "archivi.models.Enti";

        return $data;
    }

    /**
     * Controlla se una stringa può rappresentare una data.
     * @param $dateString
     * @return bool
     */
    public static function canBeDate($dateString)
    {
        if (!$dateString) {
            return false;
        }

        $dateString = str_replace(" ", "/", $dateString);
        $dateString = str_replace(array("-", "."), "/", $dateString);
        $notEmpty = $dateString;

        $roman = "(M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))";
        $dateSecRegEx = "/(([0-9]{3,5})\\s*$|([Ss][Ee][Cc]\\s*\\.\\s*(([0-9]+)|$roman)\\s*\\.?\\s*))/";
        $isDate =
            DateTime::createFromFormat("Y/m/d", $dateString) ||
            DateTime::createFromFormat("Y/m", $dateString) ||
            DateTime::createFromFormat("Y", $dateString) ||
            DateTime::createFromFormat("d/m/Y", $dateString) ||
            DateTime::createFromFormat("m/Y", $dateString) ||
            DateTime::createFromFormat("Y/m/d", $dateString) ||
            preg_match($dateSecRegEx, $dateString);

        return $notEmpty && $isDate;
    }

    /**
     * @param $intestazione
     * @return stdClass
     */
    public static function inferToponimoFromIntestazione($intestazione)
    {
        $data = new stdClass();
        $main = explode("<", str_replace(">", "", $intestazione)); //TODO: scandalosamente naive
        $infos = count($main) > 1 ? array_map('trim', explode(";", trim($main[1]))) : array();

        $data->nomeLuogo = count($main) > 0 ? trim($main[0]) : "";
        $data->comuneAttuale = count($infos) > 0 ? trim($infos[0]) : "";
        $data->denominazioneCoeva = count($infos) > 1 ? trim($infos[1]) : "";
        $data->intestazione = $intestazione;
        $data->__model = "archivi.models.Enti";

        return $data;
    }

    /**
     * @param $intestazione
     * @return stdClass
     */
    public static function inferAntroponimoFromIntestazione($intestazione)
    {
        $data = new stdClass();
        $main = explode("<", str_replace(">", "", $intestazione)); //TODO: scandalosamente naive
        $den = count($main) > 0 ? explode(",", trim($main[0])) : array();
        $infos = count($main) > 1 ? array_map('trim', explode(";", trim($main[1]))) : array();

        $dateAttivita = array();
        $dataNascita = "";
        $dataMorte = "";
        $qualifiche = array();

        $n = count($infos ?: array());
        if ($n > 0) {
            $nm = $infos[$n - 1];
            $life = array_filter(array_map('trim', explode("-", $nm)), function ($a) {
                return metafad_common_helpers_ImporterCommons::canBeDate($a);
            });
            $dataNascita = count($life) > 0 ? $life[0] : "";
            $dataMorte = count($life) > 1 ? $life[1] : "";
        }

        for ($i = 0; $i < $n - 1; $i++) {
            $info = $infos[$i];
            if (self::canBeDate(($info))) {
                $dateAttivita[] = $info;
            } else {
                $qualifiche[] = $info;
            }
        }

        $data->cognome = count($den) > 0 ? trim($den[0]) : "";
        $data->nome = count($den) > 1 ? trim($den[1]) : "";
        $data->dataNascita = $dataNascita;
        $data->dataMorte = $dataMorte;
        $data->dateAttivita = implode(" ; ", $dateAttivita);
        $data->qualificazione = implode(" ; ", $qualifiche);
        $data->intestazione = $intestazione;
        $data->__model = "archivi.models.Antroponimi";

        return $data;
    }

    /**
     * Restituisce l'intestazione se $mode è settato a "ente", "antroponimo", "toponimo".
     * <br>
     * Ovviamente, <b>la modalità scelta condiziona la struttura di $data attesa dal metodo (si vedano i model rispettivi).</b>
     * <br>
     * In caso di modalità scelta non correttamente (o non scelta) il metodo restituisce null
     * @param $data
     * @param string $mode
     * @return null|string
     */
    public static function getIntestazione($data, $mode = 'ENTE')
    {
        $ret = null;

        $identity = function ($s) {
            return $s;
        };
        switch (strtoupper($mode)) {
            case "ENTE":
                $den = $data->denominazioneEnte;
                $date = $data->dateEsistenza;
                $date = $date ? "<$date>" : '';

                $infos = array($den, $date);
                $ret = implode(' ', array_filter($infos, $identity));
                break;
            case "ANTROPONIMO":
                $c = $data->cognome;
                $n = $data->nome;
                $q = $data->qualificazione;
                $dA = $data->dateAttivita;
                $dN = $data->dataNascita;
                $dM = $data->dataMorte;
                $nm = implode('-', array_filter(array($dN, $dM), $identity));
                $cn = implode(', ', array_filter(array($c, $n), $identity));
                $ang = implode(' ; ', array_filter(array($q, $dA, $nm), $identity));
                $ang = $ang ? "<$ang>" : '';

                $infos = array($cn, $ang);
                $ret = implode(' ', array_filter($infos, $identity));
                break;
            case "TOPONIMO":
                $den = $data->nomeLuogo;
                $cA = $data->comuneAttuale;
                $dC = $data->denominazioneCoeva;
                $ang = implode(' ; ', array_filter(array($cA, $dC), $identity));
                $ang = $ang ? "<$ang>" : '';

                $infos = array($den, $ang);
                $ret = implode(' ', array_filter($infos, $identity));
                break;
            default;
        }

        return $ret;
    }


    /**
     * Trasforma ricorsivamente tutti i campi con la callback voluta. Se non ci sono callback restituisce una stringa
     * codificata in utf-8.
     * @param $data
     * @param null $callback
     * @return array|mixed
     */
    public static function recursiveHandling($data, $callback = null){
        if (is_object($data)){
            foreach ($data as $k => $v){
                $data->$k = self::recursiveHandling($v);
            }
        } else if (is_array($data)){
            foreach ($data as $k => $v){
                $data[$k] = self::recursiveHandling($v);
            }
        } else if (is_string($data)) {
            if (is_callable($callback)){
                $data = $callback($data);
            } else {
                $data = preg_replace('/\s+/', " ", $data);
                $data = preg_replace('/cc./', "carte", $data); //Punto 2 descrizione POLODEBUG-440
                if (mb_detect_encoding($data, 'UTF-8, ISO-8859-1') != 'UTF-8'){
                    $data = utf8_encode($data);
                }
            }
        }

        return $data;
    }


    /**
     * Restituisce un oggetto <id, text> per il linking ad uno STRUMAG.
     * Restituisce null se l'identifier dato in input restituisce una ricerca "vuota" di StruMAG
     * @param $identifier
     * @return null|stdClass
     */
    public static function findStruMAG($identifier){
        $ar = self::getActiveRecordStruMAG($identifier);

        $linkedStru = null;

        if ($ar && $ar->getId()) {
            $linkedStru = new stdClass();

            $linkedStru->id = $ar->getId();
            $linkedStru->text = $identifier;
        }

        return $linkedStru;
    }

    public static function getSingleImageFromMAGIdentifier($identifier){
        $ar = self::getActiveRecordMAG($identifier);

        return self::getSingleImageFromMAGActiveRecord($ar);
    }

    /**
     * Metodo per il linking di un MAG ad una scheda dell'archivistico
     * Restituisce un array con:
     * <br>
     * <ul>
     *   <li>success: se il linking ha avuto successo</li>
     *   <li>type: tipo di eventuale errore</li>
     *   <li>msg: messaggio di log proposto</li>
     * </ul>
     *
     * @param $identifier string identificativo del MAG da ricercare
     * @param $scheda mixed stdClass della scheda da linkare oppure ActiveRecord della scheda
     * @param $archiviProxy archivi_models_proxy_ArchiviProxy ArchiviProxy per il linking
     * @param $saveArchivi boolean (default true) decide se salvare la scheda subito dopo il linking
     * @param $struMagIdentifier string|null (default null) identificativo dello STRUMAG da ricercare (se null sarà uguale al MAG)
     * @return array Esito della ricerca
     */
    public static function findAndLinkMAG($identifier, $scheda, $archiviProxy, $saveArchivi = true, $struMagIdentifier = null)
    {
        $struMagIdentifier = $struMagIdentifier ?: $identifier;

        $data = method_exists($scheda, "getRawData") ? $scheda->getRawData() : $scheda;
        $data->__id = method_exists($scheda, "getId") ? $scheda->getId() : (($scheda->__id ?: $scheda->document_id) ?: $data->__id);
        $data->__model = $data->document_type ?: $data->__model;

        $conversion = array(
            "archivi.models.UnitaArchivistica" => "Unità Archivistica",
            "archivi.models.UnitaDocumentaria" => "Unità Documentaria",
            "archivi.models.ComplessoArchivistico" => "Complesso Archivistico",
            "archivi.models.ProduttoreConservatore" => "Entità",
            "archivi.models.FonteArchivistica" => "Fonte Archivistica",
            "archivi.models.SchedaBibliografica" => "Scheda Bibliografica",
            "archivi.models.SchedaStrumentoRicerca" => "Strumento di Ricerca",
            "archivi.models.Enti" => "Voce d'indice: Ente",
            "archivi.models.Antroponimi" => "Voce d'indice: Antroponimo",
            "archivi.models.Toponimi" => "Voce d'indice: Toponimo"
        );

        try{
            $linkedStru = self::findStruMAG($struMagIdentifier);
            $imgStdClass = null;

            if ($linkedStru){ //Metadato strutturale trovato, poi salvo il MAG anche
                $data->linkedStruMag = $linkedStru;
                $msg = self::updateMAG($identifier, $conversion, $data, $linkedStru) ?
                    "Record {$data->__id} successully linked to STRUMAG {$linkedStru->id}, $struMagIdentifier" :
                    "Record {$data->__id} MAG $identifier not found after a successful STRUMAG linking!";
            } else { //Metadato strutturale non trovato, cerco una singola immagine
                $imgStdClass = self::getSingleImageFromMAGIdentifier($identifier);

                if ($imgStdClass) {
                    $data->mediaCollegati = json_encode($imgStdClass);
                    $msg = self::updateMAG($identifier, $conversion, $data, $linkedStru) ?
                        "Record {$data->__id} successully linked to image {$imgStdClass->id} with MAG $identifier" :
                        "Record {$data->__id}: MAG $identifier not found after a successful singleImage linking!";
                } else {
                    $msg = "Record {$data->__id}: neither STRUMAG nor Images found for this record using identifier=$struMagIdentifier";
                }
            }

            if ($saveArchivi && ($linkedStru || $imgStdClass)){
                $archiviProxy->save($data);
            }
        } catch (Exception $ex) {
            return array(
                "success" => false,
                "type" => "EXCEPTION",
                "msg" => "Record {$data->__id} linking failed: {$ex->getMessage()}\n<br>\n"
            );
        }

        return array(
            "success" => true,
            "type" => "SUCCESS",
            "msg" => $msg
        );
    }

    /**
     * Restituisce un link per il singolo media a partire da un record MAG.
     * Restituisce null nel caso in cui l'active record del MAG non fosse valido oppure nel caso in cui non esistano.
     * I motivi per cui un'immagine può non esistere è anche dovuto al fatto che il suo dam_media_id non sia valorizzato.
     * immagini per il MAG.
     * @param $ar
     * @return null|stdClass
     */
    public static function getSingleImageFromMAGActiveRecord($ar){
        if ($ar && $ar->getId()){
            $docStruProxy = org_glizy_ObjectValues::get('org.glizy', 'application')->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
            $docStru = $docStruProxy->getRootNodeByDocumentId($ar->getId());
            $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Img')
                ->where('docstru_rootId', $docStru->docstru_id)
                ->where('docstru_type', 'img');

            $img = $it->first();
            $damId = $img ? $img->dam_media_id : 0;
            if ($it->count() == 1 && $img && $damId) {
                $media = new stdClass();
                $media->id = $damId;
                $media->title =  $img->nomenclature;
                return $media;
            }
        }
        return null;
    }


    /**
     * @param $identifier
     * @param $conversion
     * @param $data
     * @param null $linkedStru
     * @return bool
     */
    private static function updateMAG($identifier, $conversion, $data, $linkedStru = null){
        $arMAG = __ObjectFactory::createModelIterator("metafad.teca.MAG.models.Model")
            ->where("BIB_dc_identifier_index", $identifier)
            ->first();

        if ($arMAG){
            $arMAG->linkedFormType = ($data->__model) ?: "Scheda non nota" . " (Archivi)";
            $arMAG->linkedForm = (object)array("id" => $data->__id, "text" => $data->_denominazione);
            if ($linkedStru) {
                $arMAG->linkedStru = $linkedStru; //Intervento correttivo
            }
            $arMAG->save(null,false,'PUBLISHED'); //<--- Di default fa una DRAFT, e quindi non funziona la refresh.
            return true;
        } else {
            return false;
        }
    }

    /**
     * Questo metodo rigenera la consistenza delle CA/UA/UD come indicato nelle POLODEBUG-310/402.
     * <br>
     * Questo metodo restituisce l'input che ha modificato. Dunque può essere usato sia come
     * procedura che come funzione che restituisce un valore.
     * <br>
     * Ricordarsi del fatto che l'input $data verrà modificato in alcuni campi, a seconda del tipo specificato.
     * @param $data stdClass scheda CA/UA/UD da modificare
     * @param string $type tipo di scheda da modificare
     * @return mixed stdClass modificata (che fa riferimento allo stesso oggetto in input)
     */
    public static function regenerateConsistence($data, $type = 'ca')
    {
        switch (strtolower($type)) {


            case 'ca':
                $consistenze = @$data->consistenza ?: array();
                $data->consistenzaTotale =
                    implode(
                        ", ",
                        array_filter(
                            array_map(
                                function ($a) {
                                    $qtTipo = array($a->quantita ?: "", $a->quantita ? ($a->tipologia ?: "N.D.") : "");
                                    return implode(" ", array_filter($qtTipo));
                                },
                                $consistenze
                            )
                        )
                    );
                return $data;


            case 'ua':
                $eT = @$data->descrizioneFisica_tipologia ?: "";
                $eS = @$data->descrizioneFisica_supporto ?: "";
                $eS = $eS ? "($eS)" : "";
                $ext = implode(" ", array_filter(array($eT, $eS)));
                $consistenze = @$data->descrizioneFisica_consistenza ?: array();

                $data->visualizzazioneConsistenza =
                    implode(": ",
                        array_filter(
                            array(
                                $ext,
                                implode(
                                    "; ",
                                    array_filter(
                                        array_map(
                                            function ($a) {
                                                $q = @$a->consistenza_quantita ?: "";
                                                $t = $q ? (@$a->consistenza_tipologia ?: "N.D.") : "";
                                                $s = @$a->consistenza_supporto;
                                                $qtTipo = array($q, $t, $s ? "($s)" : "");
                                                return implode(" ", array_filter($qtTipo));
                                            },
                                            $consistenze
                                        )
                                    )
                                )
                            )
                        )
                    );
                return $data;
            case 'ud':


                $eT = @$data->descrizioneFisica_tipologia ?: "";
                $eS = @$data->descrizioneFisica_supporto ?: "";
                $eS = $eS ? "($eS)" : "";
                $ext = implode(" ", array_filter(array($eT, $eS)));
                $consistenze = @$data->consistenza ?: array();

                $data->visualizzazioneConsistenza =
                    implode(": ",
                        array_filter(
                            array(
                                $ext,
                                implode(
                                    "; ",
                                    array_filter(
                                        array_map(
                                            function ($a) {
                                                $q = @$a->quantita ?: "";
                                                $t = $q ? (@$a->descrizioneFisicaSupporto_tipologia ?: "N.D.") : "";
                                                $s = @$a->supporto_supporto;
                                                $qtTipo = array($q, $t, $s ? "($s)" : "");
                                                return implode(" ", array_filter($qtTipo));
                                            },
                                            $consistenze
                                        )
                                    )
                                )
                            )
                        )
                    );
                return $data;
                break;
        }

        return $data;
    }

    /**
     * @param $ex Throwable
     * @return string
     */
    public static function getThrowableString($ex){
        $arr = $ex ? array(
            "pointOfFailure" => $ex->getFile() . ":" . $ex->getLine(),
            "error" => $ex->getMessage(),
            "stacktrace" => $ex->getTraceAsString(),
            "previous" => self::getThrowableString($ex->getPrevious())
        ) : array();

        return $ex ? <<<EOF
Point of Failure: {$arr['pointOfFailure']}
Error message: {$arr['error']}
Stacktrace: {$arr['stacktrace']}
================================================================================================================================
================================================================================================================================
Previous exception:
{$arr['previous']}
EOF
            : "";

    }

    /**
     * @param $identifier
     * @return mixed|null|org_glizy_dataAccessDoctrine_ActiveRecord
     */
    public static function getActiveRecordMAG($identifier)
    {
        $ar = __ObjectFactory::createModelIterator("metafad.teca.MAG.models.Model")
            ->where("BIB_dc_identifier_index", $identifier)
            ->first();
        return $ar;
    }

    /**
     * @param $identifier
     * @return mixed|null|org_glizy_dataAccessDoctrine_ActiveRecord
     */
    public static function getActiveRecordStruMAG($identifier)
    {
        return __ObjectFactory::createModelIterator("metafad.teca.STRUMAG.models.Model")
            ->where("title", $identifier)
            ->first();
    }
}