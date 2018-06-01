<?php

class metafad_solr_helpers_MetaindiceHelper extends GlizyObject
{
    private $mappingFile;

    /**
     * @param $data stdClass Dati da salvare/mappare
     * @param $type string Tipo di dati da mappare
     * @param string $option immettere commit
     * @param stdClass $solrData Dati mandati precedentemente al core di FE
     */
    public function mapping($data, $type, $option = "commit", $solrData = null, $ecommerceInfo = null)
    {
        //Alcuni campi hanno valore cablato in base al tipo e non sono recuperabili
        //direttamente da un semplice campo
        // - domininio (Patrimonio, Archivi, Unimarc SBN)
        // - tipoSpecificoDoc (TSK, unità, monografie-periodici-spoglio)
        // - digitale: indica se l'elemento in questione ha digitale collegato

        //N.B: per SBN sarà solo necessario mandare il campo "digitale"
        if ($type == 'iccd') {
            $url = __Config::get('metafad.solr.iccd.url');
            $metaindexUrl = __Config::get('metafad.solr.metaindice.url');
        } else if ($type == 'archive') {
            $url = __Config::get('metafad.solr.archive.url');
            $metaindexUrl = __Config::get('metafad.solr.metaindice.url');
        } else if ($type == 'iccdaut') {
            $url = __Config::get('metafad.solr.iccdaut.url');
            $metaindexUrl = __Config::get('metafad.solr.metaindiceaut.url');
        } else if ($type == 'archiveaut') {
            $url = __Config::get('metafad.solr.archiveaut.url');
            $metaindexUrl = __Config::get('metafad.solr.metaindiceaut.url');
        }

        $docForSolr = new stdClass();

        $docForSolr->institutekey_s = $data->instituteKey ? $data->instituteKey : metafad_usersAndPermissions_Common::getInstituteKey();

        $ecommHelper = org_glizy_objectFactory::createObject('metafad_ecommerce_helpers_EcommerceToSolrHelper');

        $updateDateTime = new DateTime();
        $docForSolr->update_at_s = $updateDateTime->format('Y-m-d H:i:s');

        $mappingFilePath = __Paths::get('APPLICATION_TO_ADMIN') . 'classes/metafad/solr/json/metaindice.json';
        $this->mappingFile = json_decode(file_get_contents($mappingFilePath));

        //Prendo i dati dal SOLR per ricostruire l'indice
        if ($solrData == null) {
            $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', $url . 'select', 'POST', 'q=id:' . $data->__id . '&wt=json', 'application/x-www-form-urlencoded');
            $request->setAcceptType('application/json');
            $request->execute();
            $datiDaSolr = json_decode($request->getResponseBody())->response->docs[0];
        } else {
            $datiDaSolr = $solrData;
        }

        if (!$datiDaSolr) {
            return;
        }

        if ($type == 'unimarc') {
            return;
        } else if ($type == 'iccd') {
            //Valorizzazione campi particolari
            $docForSolr->id = $data->__id;
            if($data->visibility)
            {
              $docForSolr->visibility_nxs = $data->visibility;
            }
            $docForSolr->dominio_s = 'patrimonio';
            $docForSolr->dominio_t = $docForSolr->dominio_s;
            $arrayTipologie = array('D'=>'disegno','OA'=>'opera d\'arte','S'=>'stampa','F'=>'fotografia');
            $docForSolr->tipospecificodoc_s = $arrayTipologie[$data->TSK];
            $docForSolr->tipospecificodoc_t = $docForSolr->tipospecificodoc_s;
            $docForSolr->lingua_ss = $this->extractLanguage($data);
            $docForSolr->lingua_txt = $docForSolr->lingua_ss;

            $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
            $instituteKey = ($data->instituteKey) ?: metafad_usersAndPermissions_Common::getInstituteKey();
            $instituteData = $instituteProxy->getInstituteVoByKey($instituteKey);
            $instituteName = $instituteData->institute_name;

            $docForSolr->localizzazione_ss = array($instituteName);
            $docForSolr->localizzazione_txt = $docForSolr->localizzazione_ss;

            $docExtracted = $this->extractFieldsValues($type, $datiDaSolr);

            $area_digitale = $this->mappingDigitalField($data, $type);
            if ($area_digitale) {
                $docForSolr->area_digitale_ss = $area_digitale;
                $docForSolr->area_digitale_txt = $area_digitale;
            }

            $ecommerceInfo = $ecommHelper->getEcommerceInfo($data,'iccd');

            if ($ecommerceInfo)
            {
                $docForSolr->ecommerce_nxs = $ecommerceInfo;
            }

            if ($data->TSK == 'F') {
                $docForSolr->title_t = $datiDaSolr->soggetto_titolo_txt[0];
                $docForSolr->description_t = $datiDaSolr->soggetto_indicazioni_sul_soggetto_txt[0];
            } else if ($data->TSK == 'S') {
                $docForSolr->title_t = $datiDaSolr->soggetto_titolo_txt[0];
            } else if($data->TSK == 'D' || $data->TSK == 'OA') {
                $desc = $datiDaSolr->definizione_culturale_autore_txt[0] ? : $datiDaSolr->definizione_culturale_ambito_culturale_txt[0];
                $desc .= '<br/>'.$data->DESS[0]->{'DESS-element'};

                $docForSolr->title_t = $datiDaSolr->soggetto_identificazione_txt[0];
                $docForSolr->description_t = $desc;
            }
            $docForSolr = array_merge((array)$docForSolr, (array)$docExtracted);
        } else if ($type == 'archive') {
            //Valorizzazione campi particolari
            $docForSolr->id = $data->__id;
            if($data->visibility)
            {
              $docForSolr->visibility_nxs = $data->visibility;
            }
            $docForSolr->dominio_s = 'archivi';
            $docForSolr->dominio_t = $docForSolr->dominio_s;
            $docForSolr->tipospecificodoc_s = ($data->__model == 'archivi.models.UnitaArchivistica') ? 'unità archivistica' : 'unità documentaria';
            $docForSolr->tipospecificodoc_t = $docForSolr->tipospecificodoc_s;

            $root = $datiDaSolr->primo_livello_label_s;
            if ($root){
                $docForSolr->complessodappartenenza_ss = array($root);
                $docForSolr->complessodappartenenza_txt = array($root);
            }

            if($ecommerceInfo)
            {
              $docForSolr->ecommerce_nxs = $ecommerceInfo;
            }

            $docExtracted = $this->extractFieldsValues($type, $datiDaSolr);

            $area_digitale = $this->mappingDigitalField($data, $type);
            if ($area_digitale) {
                $docForSolr->area_digitale_ss = $area_digitale;
                $docForSolr->area_digitale_txt = $area_digitale;
            }

            if (is_array($datiDaSolr->un_intestazione_ss)) {
                 $docForSolr->title_t = implode($datiDaSolr->un_intestazione_ss);
            }

            if (is_array($datiDaSolr->un_descrizione_regesto_ss)) {
                $docForSolr->description_t = implode($datiDaSolr->un_descrizione_regesto_ss);
            }

            $docForSolr = array_merge((array)$docForSolr, (array)$docExtracted);
        } else if ($type == 'iccdaut') {
            //Valorizzazione campi particolari
            $docForSolr->id = $data->__id;
            $docForSolr->identificativi_s = $data->uniqueIccdId;
            $docForSolr->identificativi_t = $docForSolr->identificativi_s;
            $docForSolr->dominio_s = 'patrimonio';
            $docForSolr->dominio_t = 'patrimonio';

			$docForSolr->denominazione_nome_ss = array($data->AUTN);
			$docForSolr->denominazione_nome_txt = array($data->AUTN);

            if ($data->__model == 'AUT300.models.Model') {
                $docForSolr->tipoentita_s = ($data->AUTZ == 'F' || $data->AUTZ == 'M') ? 'persona' : 'ente';
            } else {
                if($data->AUTP == 'P'){
                  $docForSolr->tipoentita_s = 'persona';
                }
                else if($data->AUTP == 'E'){
                  $docForSolr->tipoentita_s = 'ente';
                }
            }
            $docForSolr->tipoentita_t = $docForSolr->tipoentita_s;
            $docForSolr->tiposcheda_s = ($data->__model == 'AUT300.models.Model') ? 'AUT 3.00' : 'AUT 4.00';
            $docForSolr->tiposcheda_t = $docForSolr->tiposcheda_s;

            $docExtracted = $this->extractFieldsValuesAUT($data, $type);

            $docForSolr = array_merge((array)$docForSolr, (array)$docExtracted);
        } else if ($type == 'archiveaut') {
            //Valorizzazione campi particolari
            $docForSolr->id = $data->__id;
            $docForSolr->tiposcheda_s = 'Entità';
            $docForSolr->tiposcheda_t = 'Entità';
            $docForSolr->denominazione_nome_ss = array($datiDaSolr->titolo_s);
            $docForSolr->denominazione_nome_txt = array($datiDaSolr->titolo_s);
            $docForSolr->dominio_s = 'archivi';
            $docForSolr->dominio_t = 'archivi';
            $docForSolr->tipoentita_s = strtolower($data->tipologiaChoice);
            $docForSolr->tipoentita_t = $docForSolr->tipoentita_s;

            $arrayRuoli = array();
            if ($data->complessiArchivisticiConservatore) {
                $arrayRuoli[] = 'Conservatore';
            }
            if ($data->complessiArchivisticiProduttore) {
                $arrayRuoli[] = 'Produttore';
            }
            if(!empty($arrayRuoli))
            {
              $docForSolr->ruolo_ss = $arrayRuoli;
              $docForSolr->ruolo_txt = $docForSolr->ruolo_ss;
            }
            $docExtracted = $this->extractFieldsValuesAUT($data, $type);

            $docForSolr = array_merge((array)$docForSolr, (array)$docExtracted);
        }

        if (isset($docForSolr['denominazione_titolo_ss'])) {
            $docForSolr['titolo_ordinamento_s'] = implode(" ", $docForSolr['denominazione_titolo_ss']);
        }

        if (isset($docForSolr['responsabilita_ss'])) {
            $docForSolr['autore_ordinamento_s'] = implode(" ", $docForSolr['responsabilita_ss']);
        }

        if (!$docForSolr['localizzazione_ss']) {
            $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
            $instituteKey = ($data->instituteKey) ?: metafad_usersAndPermissions_Common::getInstituteKey();
            $instituteData = $instituteProxy->getInstituteVoByKey($instituteKey);
            $docForSolr['localizzazione_ss'] = array($instituteData->institute_name);
        }

        $evt = array('type' => 'insertData', 'data' => array('data' => $docForSolr, 'option' => array(($option === "queue" ? "queue" : "commit") => true, 'url' => $metaindexUrl)));
        $this->dispatchEvent($evt);
    }

    public function extractLanguage($data)
    {
        $appoggioLingua = array();
        if ($data->TSK == 'F') {
            if ($data->DA) {
                foreach ($data->DA as $da) {
                    foreach ($da->ISE as $ise) {
                        foreach ($ise->ISEL as $isel) {
                            $appoggioLingua[] = $isel->{'ISEL-element'};
                        }
                    }
                }
            }
        } else {
            if ($data->ISR) {
                foreach ($data->ISR as $isr) {
                    $appoggioLingua[] = $isr->ISRL;
                }
            }
        }
        return $appoggioLingua;
    }

    public function extractFieldsValues($type, $datiDaSolr)
    {
        $docForSolr = new stdClass();

        foreach ($this->mappingFile->$type as $field => $values) {

            $label = str_replace(array(":", '(', ')', '/'), '_', strtolower($field));
            $label = str_replace(' ', '_', $label);

            foreach ($values as $v) {
                if ($datiDaSolr->$v) {
                    if (is_string($datiDaSolr->$v) && sizeof($values) == 1) {
                        $docForSolr->{$label . '_s'} = $datiDaSolr->$v;
                        $docForSolr->{$label . '_t'} = $docForSolr->{$label . '_s'};
                    } else if ($label == 'digitale') {
                        $digitale = $datiDaSolr->$v;
                        if (is_array($digitale)) {
                            $docForSolr->digitale_s = $digitale[0];
                        } else {
                            $docForSolr->digitale_s = $digitale;
                        }
                    } else {
                        if (!is_array($docForSolr->{$label . '_ss'})) {
                            $docForSolr->{$label . '_ss'} = array();
                        }
                        foreach ($datiDaSolr->$v as $val) {
                            array_push($docForSolr->{$label . '_ss'}, $val);
                        }
                        $docForSolr->{$label . '_txt'} = $docForSolr->{$label . '_ss'};
                    }
                }
            }

			if($datiDaSolr->digitale_idpreview_s)
			{
				$docForSolr->digitale_idpreview_s = $datiDaSolr->digitale_idpreview_s;
				$docForSolr->digitale_idpreview_t = $datiDaSolr->digitale_idpreview_s;
			}
        }
        return $docForSolr;
    }


    //Gestione caso particolare indice lingua per iccd che non recupera da SOLR

    public function mappingDigitalField($data, $type)
    {
        $area_digitale = array();
        if ($type == 'iccd') {
            if ($data->TSK == 'F' || $data->TSK == 'D' || $data->TSK == 'S') {
                $area_digitale[] = 'grafica';
            } else if ($data->TSK == 'OA') {
                $area_digitale[] = 'opera d\'arte';
            }
        } else if ($type == 'archive') {
            $model = $data->__model;

            if ($model == 'archivi.models.UnitaDocumentaria') {
                $tipo = $data->tipo;
                if ($tipo == 'Fotografia' || $tipo == 'Grafica' || $tipo == 'Disegni') {
                    $area_digitale[] = 'grafica';
                }
                $area_digitale[] = 'documenti d\'archivio';
            } else if ($model == 'archivi.models.UnitaArchivistica') {
                $area_digitale[] = 'documenti d\'archivio';
            }
        }

        return $area_digitale;
    }

    public function extractFieldsValuesAUT($data, $type)
    {
        $docForSolr = new stdClass();

        foreach ($this->mappingFile->$type as $field => $values) {

            $label = str_replace(array(":", '(', ')', '/'), '_', strtolower($field));
            $label = str_replace(' ', '_', $label);

            foreach ($values as $v) {
                if (is_string($data->$v) && sizeof($values) == 1) {
                    if($label === 'ruolo') {
                      $docForSolr->{$label . '_ss'} = array($data->$v);
                      $docForSolr->{$label . '_txt'} = $docForSolr->{$label . '_ss'};
                    }
                    else {
                      $docForSolr->{$label . '_s'} = $data->$v;
                      $docForSolr->{$label . '_t'} = $docForSolr->{$label . '_s'};
                    }
                }
                 else {
                    if (!is_array($docForSolr->{$label . '_ss'})) {
                        $docForSolr->{$label . '_ss'} = array();
                    }
                    if ($data->$v) {
                        if (is_string($data->$v)) {
                            array_push($docForSolr->{$label . '_ss'}, $data->$v);
                        } else {
                            foreach ($data->$v as $val) {
                                if (is_object($val)) {
                                    array_push($docForSolr->{$label . '_ss'}, $val->{$v . '-element'});
                                } else {
                                    array_push($docForSolr->{$label . '_ss'}, $val);
                                }
                            }
                            $docForSolr->{$label . '_txt'} = $docForSolr->{$label . '_ss'};
                        }
                    }
                }
            }
        }
        return $docForSolr;
    }

}
