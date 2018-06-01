<?php

class metafad_modules_exporter_services_catexporter_CatExporter extends GlizyObject
{
    var $dirRead;
    var $dirWrite;

    public function __construct()
    {
        $this->dirRead = glz_findClassPath('metafad/modules/exporter/services/catexporter/input', false).'/';
        $this->dirWrite = "./input/";
    }

    protected function getURI($type, $id)
    {
        return __Config::get('metafad.FE.url')."/go/$type/$id";
    }

    protected function getDateInterval($cronologia)
    {
        $a = reset(explode('-', $cronologia->estremoRemoto_codificaData));
        $b = end(explode('-', $cronologia->estremoRecente_codificaData));

        return $a.'/'.$b;
    }

    public function validate($data)
    {
        if ($data->__model == 'archivi.models.ComplessoArchivistico') {
            $xml = $this->CreaCAT($data->__id);
            $xsd = 'http://www.san.beniculturali.it/tracciato/schemaead.xsd';
        } else if ($data->__model == 'archivi.models.ProduttoreConservatore' && in_array('Produttore', $data->isProdCons)) {
            $xml = $this->CreaSP($data->__id);
            $xsd = 'http://www.san.beniculturali.it/tracciato/schemaeac.xsd';
        } else if ($data->__model == 'archivi.models.ProduttoreConservatore' && in_array('Conservatore', $data->isProdCons)) {
            $xml = $this->CreaSC($data->__id);
            $xsd = 'http://www.san.beniculturali.it/tracciato/scons.xsd';
        } else if ($data->__model == 'archivi.models.SchedaStrumentoRicerca') {
            $xml = $this->CreaSR($data->__id);
            $xsd = 'http://www.san.beniculturali.it/tracciato/strumenti.xsd';
        } else {
            return false;
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc->schemaValidate($xsd);
    }

    ///////////////////////////////////////////////////////////////////////////
    public function CreaCAT($idCA)
    {
        $docRead = new DOMDocument();
        $docRead->load($this->dirRead."CAT-CA-1-rec.xml");

        $rXpath = new DOMXPath($docRead);
        $rXpath->registerNamespace('san', $docRead->documentElement->namespaceURI);

        $recCA = org_glizy_ObjectFactory::createModel('archivi.models.ComplessoArchivistico');
        $recCA->load($idCA, 'PUBLISHED_DRAFT');
        $data = $recCA->getRawData();
        
        $rQuery = "/san:ead/san:archdesc/san:did/san:unitid/@identifier";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $this->getURI('CA', $data->codiceIdentificativoSistema);
        
        $rQuery = "/san:ead/san:archdesc/san:did/san:unitid";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "/san:ead/san:archdesc/@otherlevel";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->livelloDiDescrizione;

        if ($data->parent) {
            $parentId = $data->parent->id;
        } else {
            $parentId = $data->codiceIdentificativoSistema;
        }

        $rQuery = "/san:ead/san:archdesc/san:relatedmaterial/san:archref";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $parentId;

        $rQuery = "/san:ead/san:archdesc/san:did/san:unittitle";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->denominazione;

        $cronologia = $data->cronologia[0];
        
        $rQuery = "/san:ead/san:archdesc/san:did/san:unitdate";
        $nodo = $rXpath->query($rQuery)->item(0);
            
        if ($cronologia) {
            $nodo->nodeValue = $cronologia->estremoCronologicoTestuale;
            $rQuery = "/san:ead/san:archdesc/san:did/san:unitdate/@normal";
            $nodo = $rXpath->query($rQuery);
            $nodo->item(0)->nodeValue = $this->getDateInterval($cronologia);
        } else {
            $nodo->parentNode->removeChild($nodo);
        }
        
        if ($data->consistenzaTotale) {
            $rQuery = "/san:ead/san:archdesc/san:did/san:physdesc/san:extent";
            $nodo = $rXpath->query($rQuery);
            $nodo->item(0)->nodeValue = $data->consistenzaTotale;
        } else {
            $rQuery = "/san:ead/san:archdesc/san:did/san:physdesc";
            $nodo = $rXpath->query($rQuery)->item(0);
            $nodo->parentNode->removeChild($nodo);
        }
        
        $rQuery = "/san:ead/san:archdesc/san:did/san:abstract";
        $abstract = $rXpath->query($rQuery)->item(0);

        if ($data->descrizioneContenuto) {
            $abstract->nodeValue = $data->descrizioneContenuto;

            if ($data->linguaDescrizioneRecord) {
                $abstract->setAttribute('langcode', $data->linguaDescrizioneRecord);
            }
        } else {
            $abstract->parentNode->removeChild($abstract);
        }
        
        if ($data->produttori) {
            foreach ($data->produttori as $produttore) {
                $rQuery = "/san:ead/san:archdesc/san:did";
                $nodo = $rXpath->query($rQuery);
                $soggProd = $docRead->createElement('origination', $produttore->soggettoProduttore->id);
                $nodo->item(0)->appendChild($soggProd);
            }
        } 

        if ($data->soggettoConservatore) {
            $rQuery = "/san:ead/san:archdesc/san:did";
            $nodo = $rXpath->query($rQuery);
            $soggCons = $docRead->createElement('repository');
            $soggCons->setAttribute('id', $data->soggettoConservatore->id);
            $soggCons->setAttribute('label', 'LBC-Archivi');
            $soggCons->nodeValue = $data->soggettoConservatore->text;
            $nodo->item(0)->appendChild($soggCons);
        }

        if ($data->strumentiRicerca) {
            foreach ($data->strumentiRicerca as $strumentoRicerca) {
                $rQuery = "/san:ead/san:archdesc";
                $nodo = $rXpath->query($rQuery);
                $otherfindaid = $docRead->createElement('otherfindaid');
                $nodo->item(0)->appendChild($otherfindaid);
    
                $extref = $docRead->createElement('extref', $strumentoRicerca->linkStrumentiRicerca->id);
                $otherfindaid->appendChild($extref);
            }
        }

        $output = $docRead->saveXML($docRead->documentElement);
        return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u', ' ', $output);
    }

    ///////////////////////////////////////////////////////////////////////////
    public function CreaSP($idSP)
    {
        $docRead = new DOMDocument();
        $docRead->load($this->dirRead."CAT-SP-1-rec.xml");

        $rXpath = new DOMXPath($docRead);
        $rXpath->registerNamespace('san', $docRead->documentElement->namespaceURI);

        $recSP = org_glizy_ObjectFactory::createModel('archivi.models.ProduttoreConservatore');
        $recSP->load($idSP, 'PUBLISHED_DRAFT');
        $data = $recSP->getRawData();

        $rQuery = "/san:eac-cpf/san:control/san:otherRecordId";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $typeMap = array(
            'Ente' => 'corporateBody',
            'Persona' => 'person',
            'Famiglia' => 'family'
        );

        $rQuery = "/san:eac-cpf/san:cpfDescription/san:identity";
        $nodo = $rXpath->query($rQuery);
        $elET = $docRead->createElement('entityType', $typeMap[$data->tipologiaChoice]);
        $nodo->item(0)->appendChild($elET);

        $rQuery = "/san:eac-cpf/san:control/san:sources/san:source";
        $nodo = $rXpath->query($rQuery);
        $url = $this->getURI('SP', $data->codiceIdentificativoSistema);
        $nodo->item(0)->setAttribute('xlink:href', $url);
        
        if ($data->tipologiaChoice=="Ente"){
            $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:descriptiveEntries";
            $nodo = $rXpath->query($rQuery);
            $elDE = $docRead->createElement('descriptiveEntry');
            $elTE = $docRead->createElement('term', $data->tipologiaEnte);
            $elDN = $docRead->createElement('descriptiveNote', 'tipologia ente');
            $nodo->item(0)->appendChild($elDE)->appendChild($elTE)->parentNode->appendChild($elDN);

            $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:descriptiveEntries";
            $nodo = $rXpath->query($rQuery);
            $elDE = $docRead->createElement('descriptiveEntry');
            $elTE = $docRead->createElement('term', $data->condizioneGiuridica[0]->listCondizioneGiuridica);
            $elDN = $docRead->createElement('descriptiveNote', 'natura giuridica');
            $nodo->item(0)->appendChild($elDE)->appendChild($elTE)->parentNode->appendChild($elDN);
        }

        if ($data->tipologiaChoice == "Persona"){
            $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:descriptiveEntries";
            $nodo=$rXpath->query($rQuery);
            $elDE=$docRead->createElement('descriptiveEntry');
            $elTE=$docRead->createElement('term', $data->titoloEntita[0]->inputTitolo);
            $elDN=$docRead->createElement('descriptiveNote', 'titolo nobiliare');
            $nodo->item(0)->appendChild($elDE)->appendChild($elTE)->parentNode->appendChild($elDN);
            
            $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:descriptiveEntries";
            $nodo=$rXpath->query($rQuery);
            $elDE=$docRead->createElement('descriptiveEntry');
            $elTE=$docRead->createElement('term', $data->attivitaProfessioneQualifica[0]->inputAttivitaProfessioneQualifica);
            $elDN=$docRead->createElement('descriptiveNote', 'professione');
            $nodo->item(0)->appendChild($elDE)->appendChild($elTE)->parentNode->appendChild($elDN);
        }
        
        if ($data->ente_famiglia_denominazione) {
            foreach ($data->ente_famiglia_denominazione as $ente_famiglia_denominazione) {
                $rQuery = "/san:eac-cpf/san:cpfDescription/san:identity";
                $nodo=$rXpath->query($rQuery);
                $elNE=$docRead->createElement('nameEntry');
                $elPA=$docRead->createElement('part', $ente_famiglia_denominazione->entitaDenominazione);
                $nodo->item(0)->appendChild($elNE)->appendChild($elPA);
                
                if ($ente_famiglia_denominazione->ente_famiglia_cronologia){
                    foreach ($ente_famiglia_denominazione->ente_famiglia_cronologia as $ente_famiglia_cronologia) { 
                        $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:existDates/san:dateSet";
                        $nodo=$rXpath->query($rQuery);
                        $elDA=$docRead->createElement('date',$ente_famiglia_cronologia->estremoCronologicoTestuale);
                        $attrSD=$docRead->createAttribute('standardDate');
                        $attrSD->value = $this->getDateInterval($ente_famiglia_cronologia);
                        $attrLT=$docRead->createAttribute('localType');
                        $attrLT->value = strtolower($ente_famiglia_cronologia->ente_famiglia_qualificaData);
                        $nodo->item(0)->appendChild($elDA)->appendChild($attrSD)->parentNode->appendChild($attrLT);
                    }
                }
            }
        }

        if ($data->persona_denominazione) {
            foreach ($data->persona_denominazione as $persona_denominazione) {
                $rQuery = "/san:eac-cpf/san:cpfDescription/san:identity";
                $nodo=$rXpath->query($rQuery);
                $elNE=$docRead->createElement('nameEntry');
                $elPA=$docRead->createElement('part',$data->persona_denominazione[$i]->entitaDenominazione);
                $nodo->item(0)->appendChild($elNE)->appendChild($elPA);

                if ($persona_denominazione->persona_cronologia) {
                    foreach ($persona_denominazione->persona_cronologia as $persona_cronologia) { 
                        $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:existDates/san:dateSet";
                        $nodo=$rXpath->query($rQuery);
                        $elDA=$docRead->createElement('date', $persona_cronologia->estremoCronologicoTestuale);
                        $attrSD=$docRead->createAttribute('standardDate');
                        $attrSD->value = $this->getDateInterval($persona_cronologia);
                        $attrLT=$docRead->createAttribute('localType');
                        $attrLT->value = strtolower($persona_cronologia->persona_qualifica);
                        $nodo->item(0)->appendChild($elDA)->appendChild($attrSD)->parentNode->appendChild($attrLT);
                    }
                }
            }
        }

        if ($data->luogoEnte) {
            foreach ($data->luogoEnte as $luogoEnte) {
                $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:placeDates";
                $nodo=$rXpath->query($rQuery);
                $elPD=$docRead->createElement('placeDate');
                $elPL=$docRead->createElement('place', $luogoEnte->luogoEnte_nomeLuogo);
                $elDN=$docRead->createElement('descriptiveNote', strtolower($luogoEnte->luogoEnte_qualificaLuogo));
                $nodo->item(0)->appendChild($elPD)->appendChild($elPL)->parentNode->appendChild($elDN);
                
                if ($luogoEnte->luogoEnte_cronologia) {
                    foreach ($luogoEnte->luogoEnte_cronologia as $luogoEnte_cronologia) { 
                        $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:existDates/san:dateSet";
                        $nodo=$rXpath->query($rQuery);
                        $elDA=$docRead->createElement('date', $luogoEnte_cronologia->estremoCronologicoTestuale);
                        $attrSD=$docRead->createAttribute('standardDate');
                        $attrSD->value = $this->getDateInterval($luogoEnte_cronologia);
                        $attrLT=$docRead->createAttribute('localType');
                        $attrLT->value = strtolower($luogoEnte_cronologia->luogoEnte_qualificaData);
                        $nodo->item(0)->appendChild($elDA)->appendChild($attrSD)->parentNode->appendChild($attrLT);
                    }
                }
            }
        }

        $rQuery = "/san:eac-cpf/san:cpfDescription/san:description/san:biogHist/san:abstract";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->storiaBiografiaStrutturaAmministrativa;

        if ($data->complessiArchivisticiProduttore) {
            foreach ($data->complessiArchivisticiProduttore as $complessiArchivisticiProduttore) {
                $rQuery = "/san:eac-cpf/san:cpfDescription/san:relations";
                $nodo=$rXpath->query($rQuery);
                $elRR=$docRead->createElement('resourceRelation');
                $elRE=$docRead->createElement('relationEntry', $complessiArchivisticiProduttore->inputComplessiArchivistici->id);
                $attrRR=$docRead->createAttribute('resourceRelationType');
                $attrRR->value = "creatorOf";
                $nodo->item(0)->appendChild($elRR)->appendChild($elRE)->parentNode->appendChild($attrRR);
            }
        }

        if ($data->soggettiProduttori) {
            foreach ($data->soggettiProduttori as $soggettoProduttore) {
                $rQuery = "/san:eac-cpf/san:cpfDescription/san:relations";
                $nodo=$rXpath->query($rQuery);
                $elCR=$docRead->createElement('cpfRelation');
                $elRE=$docRead->createElement('relationEntry', $soggettoProduttore->inputSoggettiProduttori->id);
                $attrLT=$docRead->createAttribute('localType');
                $attrLT->value = $soggettoProduttore->inputSoggettiProduttori->text;
                $nodo->item(0)->appendChild($elCR)->appendChild($elRE)->parentNode->appendChild($attrLT);
            }
        }

        $output = $docRead->saveXML($docRead->documentElement);
        return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u', ' ', $output);
    }

    ///////////////////////////////////////////////////////////////////////////
    public function CreaSC($idSC)
    {
        $docRead = new DOMDocument();
        $docRead->load($this->dirRead."CAT-SC-1-rec.xml");

        $rXpath = new DOMXPath($docRead);
        $rXpath->registerNamespace('scons', $docRead->documentElement->namespaceURI);

        $recSP = org_glizy_ObjectFactory::createModel('archivi.models.ProduttoreConservatore');
        $recSP->load($idSC, 'PUBLISHED_DRAFT');
        $data = $recSP->getRawData();

        $rQuery = "/scons:scons/scons:identifier";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->setAttribute('href', $this->getURI('SC', $data->codiceIdentificativoSistema));

        $rQuery = "/scons:scons/scons:identifier/scons:recordId";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "/scons:scons/scons:tipologia";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->tipologiaChoice;

        // TODO 
        // /scons/formaautorizzata -> ci va quella con la qualifica ‘Denominazione principale’
        // in /scons/formeparallele le altre

        if ($data->ente_famiglia_denominazione) {
            foreach ($data->ente_famiglia_denominazione as $ente_famiglia_denominazione) {
                $rQuery = "/scons:scons/scons:formaautorizzata";
                $nodo=$rXpath->query($rQuery);
                $nodo->item(0)->nodeValue = $ente_famiglia_denominazione->entitaDenominazione;

                $rQuery = "/scons:scons/scons:acronimo";
                $nodo = $rXpath->query($rQuery)->item(0);

                if ($ente_famiglia_denominazione->ente_famiglia_acronimo) {
                    $nodo->nodeValue = $ente_famiglia_denominazione->ente_famiglia_acronimo;
                } else {
                    $nodo->parentNode->removeChild($nodo);
                }
            }
        }
        
        $rQuery = "/scons:scons/scons:descrizione";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = "Cenni storici istituzionali: ".$data->cenniStoriciIstituzionali." - Patrimonio e politiche di gestione e di acquisizione: ".$data->sog_cons_patrimonio." - Note: ".$data->sog_cons_note;
        
        $rQuery = "/scons:scons/scons:sitoweb/@href";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->sog_cons_url;
        
        if ($data->sog_cons_sedi) {
            foreach ($data->sog_cons_sedi as $sede) {
                $rQuery = "/scons:scons/scons:sitoweb";
                $sitoweb = $rXpath->query($rQuery)->item(0);

                $localizzazione = $docRead->createElement('localizzazione');
                $sitoweb->parentNode->insertBefore($localizzazione, $sitoweb);
    
                $localizzazione->setAttribute('paese', $sede->sog_cons_sedi_stato);
                $localizzazione->setAttribute('provincia', $sede->sog_cons_sedi_provincia);
                $localizzazione->setAttribute('comune', $sede->sog_cons_sedi_comune);
                $localizzazione->setAttribute('cap', $sede->sog_cons_sedi_cap);
                $localizzazione->nodeValue = $sede->sog_cons_sedi_indirizzoTestuale;
                
                $rQuery = "/scons:scons/scons:servizi";
                $nodo = $rXpath->query($rQuery);
                $nodo->item(0)->nodeValue = $sede->sog_cons_sedi_indirizzoTestuale;
            }
        }
        
        $rQuery = "/scons:scons/scons:altroaccesso";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->condizioniAccesso;

        $output = $docRead->saveXML($docRead->documentElement);
        return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u', ' ', $output);
    }

    ///////////////////////////////////////////////////////////////////////////
    public function CreaSR($idSR)
    {
        $docRead = new DOMDocument();
        $docRead->load($this->dirRead."CAT-SR-1-rec.xml");
        
        $rXpath = new DOMXPath($docRead);
        $rXpath->registerNamespace('san', $docRead->documentElement->namespaceURI);

        $recSR = org_glizy_ObjectFactory::createModel('archivi.models.SchedaStrumentoRicerca');
        $recSR->load($idSR, 'PUBLISHED_DRAFT');
        $data = $recSR->getRawData();

        $rQuery = "/san:ead/san:eadheader/san:eadid";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;
        $uri = $this->getURI('SR', $data->codiceIdentificativoSistema);
        $nodo->item(0)->setAttribute('URL', $uri);

        $rQuery = "/san:ead/san:eadheader/san:filedesc/san:editionstmt/san:edition/san:extptr";
        $nodo = $rXpath->query($rQuery);
        $nodo->item(0)->setAttribute('href', $uri);
        
        $rQuery = "/san:ead/san:eadheader/san:filedesc/san:notestmt/san:note";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->tipoSupporto;
        
        $rQuery = "/san:ead/san:eadheader/san:filedesc/san:publicationstmt/san:date";
        $nodo=$rXpath->query($rQuery);
        $nodo->item(0)->nodeValue = $data->cronologiaRedazione[0]->estremoCronologicoTestuale;
        
        if ($data->autoreStrumentoRicerca) {
            $authors = array();
            foreach ($data->autoreStrumentoRicerca as $autoreStrumentoRicerca) {
                $authors[] = $autoreStrumentoRicerca->nomeAutore." ".$autoreStrumentoRicerca->cognomeAutore;
            }
            $rQuery = "/san:ead/san:eadheader/san:filedesc/san:titlestmt";
            $nodo = $rXpath->query($rQuery);
            $elAU = $docRead->createElement('author', implode(', ', $authors));
            $nodo->item(0)->appendChild($elAU);
        }

        if ($data->complessoArchivistico) {
            foreach ($data->complessoArchivistico as $complessoArchivistico) {
                $rQuery = "/san:ead/san:archdesc/san:did";
                $nodo=$rXpath->query($rQuery);
                $elUI=$docRead->createElement('unitid', $complessoArchivistico->complessoArchivisticoLink->id);
                $nodo->item(0)->appendChild($elUI);
            }
        }

        $rQuery = "/san:ead/san:eadheader/san:filedesc/san:titlestmt";
        $nodo=$rXpath->query($rQuery);
        $elTP=$docRead->createElement('titleproper',$data->titoloNormalizzato);
        $nodo->item(0)->appendChild($elTP);

        $output = $docRead->saveXML($docRead->documentElement);
        return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u', ' ', $output);
    }
}
