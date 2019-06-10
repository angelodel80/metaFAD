<?php
class metafad_sbn_modules_importer_helpers_ImportSbnHelper extends GlizyObject
{
  private $hasMediaIdList = array();
  private $idListNew = array();
  private $idListOld = array();

  public function importJsonToDb($startingDir, $modelName, $documentType, $docType, $uploadType)
  {
    $siteCode = 'poloDigitale';
    $fileDirList = scandir($startingDir);

    $jsonToImport = 100;
    //Impostare a true se non si è sicuri di importare dati per cui si ha un model corretto
    $moduleName = 'solr';
    $fields = ($modelName == 'metafad.sbn.modules.sbnunimarc.model.Model') ? $this->getFieldsUnimarc() : $this->getFieldsAuthority();
    $arrayCampi = $this->getArrayCampi();
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '2048M');

    if ($uploadType == 'delete') {
      $this->deleteAll($docType);
      $this->deleteAllFromModel($documentType);
    }

    $count = 1;
    $countCommitQueue = 0;
    $jsonToCommit = array();

    //campi da ignorare per iterazione controllo solr
    $fieldToIgnore = array("id", "filename", "isbd", "created", "mapVersion", "type", "siteCode");
    $fieldToIndex = array("author", "publicationDate", "inventory", "documentType", "title");

    //Esploro ogni sottocartella
    $fileList = scandir($startingDir);
    $actualDir = $startingDir . '/';
    //Apro ogni file
    $fileListCount = count($fileList) - 1;
    foreach ($fileList as $key => $dir) {
      if ($dir != '..' && $dir != '.' && $dir != '.DS_Store') {
        foreach (scandir($actualDir . $dir) as $k => $value) {
          if ($value != '..' && $value != '.' && $value != '.DS_Store') {
            if ($count >= $startFrom) {
              if ($countCommitQueue === $jsonToImport) {
                $jsonToCommit = json_encode($jsonToCommit);
                $this->indexSolr($jsonToCommit, $countCommitQueue);
                $countCommitQueue = 0;
                $jsonToCommit = array();
              }
              $json = json_decode(file_get_contents($actualDir . $dir . '/' . $value));
              $model = org_glizy_ObjectFactory::createModelIterator($modelName);
              if ($uploadType == 'increment' || $uploadType == 'dbonly') {
                $model = $model->where('id', $json->id)->first();
                if (!$model) {
                  $model = org_glizy_ObjectFactory::createModel($modelName);
                  array_push($this->idListNew, $json->id);
                } else if ($modelName == 'metafad.sbn.modules.sbnunimarc.model.Model') {
                  array_push($this->idListOld, $json->id);
                  if ($this->checkMediaInfo($model)) {

                    $this->hasMediaIdList[] = $json->id;
                  }
                }
              } else if ($uploadType == 'delete') {
                $model = $model->where('id', $json->id)->first();
                if ($model) {
                  $model->delete();
                  $this->deleteFromSolrById($json->id);
                }
                $model = org_glizy_ObjectFactory::createModel($modelName);
              }
              $existingFieldsList = $model->getFields();
              //Estraggo il JSON e eseguo una lettura del file
              $jsonToSend = array();
              //Inserisco valori nel model
              $model->id = $json->id;
              $this->idList[] = $json->id;
              $model->filename = $json->filename;
              $model->isdb = $json->isbd;
              $model->created = $json->created;
              $model->mapVersion = $json->mapVersion;
              $model->type = $moduleName;
              $model->siteCode = $siteCode;

              //Inserisco valori nel json da mandare a solr (non indicizzo, usando _nxs, questi valori)
              $jsonToSend["id"] = $json->id;
              $jsonToSend["isbd_nxs"] = ($json->isbd) ? $json->isbd : "";
              $jsonToSend["type_nxs"] = $moduleName;
              $jsonToSend["siteCode_nxs"] = $siteCode;
              //Estraggo i campi

              //Rivedere suffissi solr per campi ricerca avanzata
              if ($json->fields) {
                foreach ($json->fields as $key => $field) {
                  $arrayForModel = array();
                  $fieldName = $field->name;
                  $fieldValue = $field->values;
                  $fieldToSend = array();
                  $vidToSend = array();

                  $arrayForModel[$fields[$fieldName]] = array();
                  $arrayModelValues = array();
                  foreach ($fieldValue as $key => $value) {
                    //Scorro i campi e salvo in array di appoggio
                    foreach ($value as $k => $v) {
                      if ($k == 'html') {
                        $arrayModelValues[] = $v;
                        break;
                      } else if ($k == 'plain') {
                        $arrayModelValues[] = $v;
                        break;
                      }
                    }
                    $fieldToSend[] = $value->plain;
                  }
                  $arrayForModel[$fields[$fieldName]] = $arrayModelValues;

                  foreach ($fieldValue as $key => $value) {
                    if (isset($value->vid)) {
                      $vidToSend[] = $value->vid;
                    }
                  }

                  $isSingleField = ($arrayCampi[$fieldName] <= 1) ? true : false;

                  //Elaboro il nome del campo per solr
                  if ($fieldName == 'hasParts' || $fieldName == 'IsPartOf') {
                    $fieldNameSolr = $fieldName . "_nxs";
                  } else {
                    $fieldNameSolr = str_replace(array("'", ":", "[", "]", "(", ")", "/"), "", $fieldName);
                    $fieldNameSolr = str_replace(" ", "_", $fieldNameSolr);
                    $fieldNameSolr = ($arrayCampi[$fieldName] <= 1) ? $fieldNameSolr . "_s" : $fieldNameSolr . "_ss";
                  }

                  //Elaboro field name del model
                  $fieldNameModel = $fields[$fieldName];

                  //salvo campo nel model e nel json
                  //salvo a seconda del tipo di campo (multiplo o meno) nello stesso modo
                  //perché ogni campo può avere più valori
                  if (array_key_exists($fieldNameModel, $existingFieldsList)) {
                    $model->$fieldNameModel = $arrayForModel[$fields[$fieldName]];
                  }

                  if ($isSingleField) {
                    $jsonToSend[$fieldNameSolr] = $fieldToSend[0];
                    $jsonToSend[$fieldNameSolr . '_lower'] = strtolower($jsonToSend[$fieldNameSolr]);
                  } else {
                    $jsonToSend[$fieldNameSolr] = $fieldToSend;
                    $jsonToSend[$fieldNameSolr . '_lower'] = $this->lowerArray($fieldToSend);
                  }
                }
              }
              //Aggiungo _store e _data per evitare il più possibile query verso il
              //DB in fase di ricerca.
              $jsonToSend["docType_s"] = $docType;
              $jsonToSend["_store"] = '';
              $jsonToSend["_data"] = '';
              $jsonToSend["document_id_nxs"] = $model->save(null, false, 'PUBLISHED');
              $jsonToCommit[] = $jsonToSend;
              unset($jsonToSend);
              unset($fieldToSend);
              unset($model);
              unset($json);
              $countCommitQueue++;
            }
          }
        }
      }
      $count++;
    }
    $this->indexSolr($jsonToCommit, $countCommitQueue);

    return array('hasMediaIdList' => $this->hasMediaIdList, 'idListNew' => $this->idListNew, 'idListOld' => $this->idListOld);
  }

  public function importFE($folder, $output, $profile, $folderWeb, $uploadType)
  {
    $dirContent = scandir($folderWeb);
    foreach ($dirContent as $file) {
      if (pathinfo($file, PATHINFO_EXTENSION) == 'MRC' || pathinfo($file, PATHINFO_EXTENSION) == 'mrc') {
        $input = $file;
        break;
      }
    }
    if (!$input) {
      return false;
    } else {
      $clear = ($uploadType == 'delete') ? '' : '&clear=false';
      $importFEService = __Config::get('metafad.sbn.import');
      $importFEService = str_replace(array('##filename##', '##directory##'), array($folder . '/' . $input, $output), $importFEService) . $profile . $clear;
      return json_decode(file_get_contents($importFEService))->message;
    }
  }

  public function updateSbnDigitale($list)
  {
    $fi = org_glizy_objectFactory::createObject('metafad.viewer.helpers.FirstImage');
    $kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');

    foreach ($list as $id) {
      $firstImage = $fi->execute($id, 'sbn');

      if ($firstImage) {
        $firstImage = $firstImage['firstImage'];
        $url = __Config::get('metafad.service.updateSbnDigitale');
        $idpreview = ($firstImage) ? '&idpreview=' . $firstImage : '';

        $digitale = '&digitale=true';

        $r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url . '?id=' . strtoupper($id) . $digitale . $idpreview, 'GET', '', 'application/json');
        $r->execute();
      } else {
        $kardexService->updateFE($id);
      }
    }
  }

  public function updateSbnEcommerce($list)
  {
    $sbnProxy = org_glizy_ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.model.proxy.UpdateSbnProxy');
    foreach ($list as $id) {
      $it = org_glizy_ObjectFactory::createModelIterator('metafad.sbn.modules.sbnunimarc.model.Model');
      $it->where('id', $id)->first();
      $data = $it->getRawData();
      $data->__id = $it->getId();
      $sbnProxy->updateSbnEcommerce($data);
    }
  }

  public function checkMediaInfo($record)
  {
    if ($record->linkedStruMag || $record->linkedMedia || $record->linkedInventoryMedia || $record->linkedInventoryStrumag) {
      return true;
    }
  }

  function arrayToString($a)
  {
    $string = '';
    foreach ($a as $val) {
      $string .= $val . ',';
    }
    return trim($string, ", ");
  }

  function indexSolr($json, $n)
  {
    $request = org_glizy_ObjectFactory::createObject(
      'org.glizy.rest.core.RestRequest',
      __Config::get('metafad.solr.url') . 'update?commit=true',
      'POST',
      is_string($json) ? $json : json_encode($json),
      'application/json'
    );
    $request->execute();
    $r = $request->getResponseInfo();
  }

  function deleteAll($docType)
  {
    $request = org_glizy_ObjectFactory::createObject(
      'org.glizy.rest.core.RestRequest',
      __Config::get('metafad.solr.url') . 'update?commit=true',
      'POST',
      '{"delete": { "query" : "docType_s:' . $docType . '" }}',
      'application/json'
    );
    $request->execute();
  }

  function deleteFromSolrById($id)
  {
    $request = org_glizy_ObjectFactory::createObject(
      'org.glizy.rest.core.RestRequest',
      __Config::get('metafad.solr.url') . 'update?commit=true',
      'POST',
      '{"delete": { "query" : "id:' . $id . '" }}',
      'application/json'
    );
    $request->execute();
  }

  function deleteAllFromModel($documentType)
  {
    $conn = org_glizy_dataAccessDoctrine_DataAccess::getconnection(0);
    $sql = <<<EOD
    DELETE doc, detail, documents_index_date_tbl, documents_index_datetime_tbl,
    documents_index_fulltext_tbl, documents_index_int_tbl, documents_index_text_tbl, documents_index_time_tbl
    FROM `documents_tbl` doc
    JOIN documents_detail_tbl detail on detail.document_detail_FK_document_id = doc.document_id
    LEFT JOIN documents_index_date_tbl on documents_index_date_tbl.document_index_date_FK_document_detail_id = detail.document_detail_id
    LEFT JOIN documents_index_datetime_tbl on documents_index_datetime_tbl.document_index_datetime_FK_document_detail_id = detail.document_detail_id
    LEFT JOIN documents_index_fulltext_tbl on documents_index_fulltext_tbl.document_index_fulltext_FK_document_detail_id = detail.document_detail_id
    LEFT JOIN documents_index_int_tbl  on documents_index_int_tbl.document_index_int_FK_document_detail_id = detail.document_detail_id
    LEFT JOIN documents_index_text_tbl on documents_index_text_tbl.document_index_text_FK_document_detail_id = detail.document_detail_id
    LEFT JOIN documents_index_time_tbl  on documents_index_time_tbl.document_index_time_FK_document_detail_id = detail.document_detail_id
    WHERE `document_type` = '$documentType'
EOD;
    $conn->exec($sql);
  }

  function getFieldsUnimarc()
  {
    return array(
      "Altra edizione su stesso supporto" => "otherEditionSameSupport",
      "Altri numeri standard" => "otherStandardNum",
      "Altri titolo correlati" => "otherTitleRelated",
      "Autore" => "author",
      "Autore [sintetico]" => "authorSintetic",
      "Autore secondario" => "authorSecondary",
      "Classificazione Dewey" => "deweyClassification",
      "Codice Dewey" => "deweyCode",
      "Codice identificativo" => "identificationCode",
      "Collezione" => "collection",
      "Composizione (mat. musicale)" => "composition",
      "Continuazione di" => "continuationOf",
      "Continuazione in parte di" => "continuationInPartOf",
      "Data" => "date",
      "Data pubblicazione" => "publicationDate",
      "Dati codificati per titolo uniforme musicale" => "cdUniformTitleMusic",
      "Dati codificati: contenuto caratteristico dell'espressione" => "cdExpressionContent",
      "Dati codificati: elaborazioni musicali (elaborazione)" => "cdElaboration",
      "Dati codificati: materiale antico" => "cdOldMaterial",
      "Dati codificati: monografie" => "cdMonographic",
      "Dati codificati: musica a stampa - designazione specifica del materiale" => "cdMusicPrint",
      "Dati codificati: perodici" => "cdPeriodic",
      "Dati codificati: tipo di supporto" => "cdSupportType",
      "Dati per la elaborazione" => "elaborationType",
      "Descrizione Dewey" => "deweyDescription",
      "Descrizione fisica" => "phisicalDescription",
      "Descrizioni delle serie / collezioni" => "seriesCollectionDescription",
      "Editore" => "editor",
      "Edizione" => "edition",
      "Fuso con" => "attachedTo",
      "ISBN" => "ISBN",
      "ISMN" => "ISMN",
      "ISSN" => "ISSN",
      "Identificativo di versione" => "versionId",
      "Impronta" => "print",
      "Inventari e collocazioni delle copie" => "inventoryCollectionCopies",
      "Inventario" => "inventory",
      "Istituzione" => "istitution",
      "Legame a livelli intermedi (subset)" => "subset",
      "Legame al livello piu' elevato (set)" => "set",
      "Legame allo spoglio" => "examinationBond",
      "Legame parte analitica - padre" => "analiticPartBond",
      "Lingua" => "language",
      "Livello bibliografico" => "bibliographicLevel",
      "Localizzazione" => "localization",
      "Localizzazione delle copie in formato elettronico" => "electronicLocalization",
      "Luogo" => "location",
      "Luogo di pubblicazione normalizzato" => "publicationLocationNormalized",
      "Marca editoriale" => "editorialMark",
      "Nome di gruppo(ente): responsabilita' alternativa" => "gnAlternativeResponsability",
      "Nome di gruppo(ente): responsabilita' principale" => "gnMainResponsability",
      "Nome di gruppo(ente): responsabilita' secondaria" => "gnSecondaryResponsability",
      "Nome di gruppo: forma non accettata" => "gnNotAccepted",
      "Nome di persona: forma non accettata" => "pnNotAccepted",
      "Nome di persona: responsabilita' alternativa" => "pnAlternativeResponsability",
      "Nome di persona: responsabilita' principale" => "pnMainResponsability",
      "Nome di persona: responsabilita' secondaria" => "pnSecondaryResponsability",
      "Note di contenuto" => "contentNotes",
      "Note generali" => "generalNotes",
      "Note relative all'esemplare" => "exampleNotes",
      "Note relative alla periodicita' di pubblicazione" => "periodicityNote",
      "Note relative alla provenienza" => "originNotes",
      "Note relative alla responsabilita'" => "responsabilityNotes",
      "Note tra titoli" => "titlesNotes",
      "Numerazione" => "numeration",
      "Numero Bibliografica Nazionale" => "NBN",
      "Numero di monografie e spogli collegati" => "monographyNumber",
      "Numero editoriale (musica)" => "musicEditorialNumber",
      "Orgine del record" => "recordOrigin",
      "Paese" => "country",
      "Presentazione" => "presentation",
      "Pubblicazione" => "publication",
      "Responsabilita' principale" => "mainResponsability",
      "Risorsa elettronica url" => "electronicResourceUrl",
      "Soggetto" => "subject",
      "Tipo documento" => "documentType",
      "Titolo" => "title",
      "Titolo [sintetica]" => "titleSintetic",
      "Titolo alternativo" => "titleAlternative",
      "Titolo chiave" => "titleKey",
      "Titolo parallelo" => "titleParallel",
      "Titolo uniforme" => "titleUniform",
      "Traduzione di" => "translationOf",
      "Unimarc" => "unimarc",
      "Nota informativa" => "informativeNote",
      "Continua con" => "continueWith",
      "Si scinde in" => "splitIn",
      "hasParts" => "hasParts",
      "IsPartOf" => "IsPartOf",
      "Datazione" => "datation",
      "Numero internazionale articolo (ean)" => "ean",
      "Rappresentazione (mat. musicale)" => "rapresentation",
      "Personaggi e interpreti (mat. musicale)" => "interpreters",
      "Abstract" => "abstract",
      "Dati codificati: materiale grafico" => "codedDataGraphic",
      "Note sulla risorsa elettronica" => "electronicResourceNotes",
      "Dati codificati: materiale cartografico - dati generali" => "codedDataCartographic",
      "Dati codificati: materiale cartografico - caratteristiche fisiche" => "codedDataCartographicCar",
      "Titolo di raccolta fattizia" => "titleFictitious",
      "Titolo [sintetico]" => "titleSinteticTwo",
      "Inventari e collocazioni delle copie (BE)" => "inventoryCollectionCopiesBE",
      "Risorsa elettronica" => "electronicResource",
      "Tipo di mediazione" => "mediationType",
      "Data_inizio" => "Data_inizio",
      "Data_range" => "Data_range",
      "181_Forma del contenuto" => "181_Forma_del_contenuto",
      "181_Tipo_di_contenuto" => "181_Tipo_di_contenuto",
      "181_Tipo di contenuto" => "181_Tipo_di_contenuto",
      "181_Movimento" => "181_Movimento",
      "181_Dimensionalita'" => "181_Dimensionalita",
      "181_Sensorialita'" => "181_Sensorialita",
      "Titoli collegati" => "Titoli_collegati",
      "Luogo 2" => "Luogo_2",
      "Variante del titolo" => "Variante_del_titolo",
      "Autore (intestazione)" => "Autore_intestazione",
      "Collezione [Faccetta]" => "Collezione_Faccetta",
      "Stampatore" => "Stampatore",
      "Editore 2" => "Editore_2",
      "Data_fine" => "Data_fine",
      "Stampatore 2" => "Stampatore_2",
      "ISBN2" => "ISBN2",
      "Titolo uniforme, nome" => "Titolo_uniforme_nome",
      "Titolo uniforme 2" => "Titolo_uniforme_2",
      "140_Illustrazioni" => "140_Illustrazioni",
      "140_Illustrazioni a tutta pagina" => "140_Illustrazioni_a_tutta_pagina",
      "140_Codice letterario" => "140_Codice_letterario",
      "140_Codice per la pubblicazione" => "140_Codice_per_la_pubblicazione",
      "140_Non usato" => "140_Non_usato",
      "Numero MID" => "Numero_MID",
      "ISSN2" => "ISSN2",
      "105_Tipo testo letterario" => "105_Tipo_testo_letterario",
      "140_Forma del contenuto" => "140_Forma_del_contenuto",
      "125_Codice presentazione" => "125_Codice_presentazione",
      "125_Indicatore di parti" => "125_Indicatore_di_parti",
      "128_Organico sintetico" => "128_Organico_sintetico",
      "Organico sintetico" => "Organico_sintetico",
      "929_Datazione della composizione" => "929_Datazione_della_composizione",
      "929_Sezioni" => "929_Sezioni",
      "929_Titolo di ordinamento" => "929_Titolo_di_ordinamento",
      "929_Identificativo del titolo uniforme collegato" => "929_Identificativo_del_titolo_uniforme_collegato",
      "929_Numero di catalogo tematico" => "929_Numero_di_catalogo_tematico",
      "Numero catalogo tematico" => "Numero_catalogo_tematico",
      "Organico analitico" => "Organico_analitico",
      "ISMN2" => "ISMN2",
      "Personaggi" => "Personaggi",
      "Sartori" => "Sartori",
      "929_Numero d'ordine" => "929_Numero_d_ordine",
      "929_Numero d'opera" => "929_Numero_d_opera",
      "929_Tonalita' della composizione" => "929_Tonalita_della_composizione",
      "Numero d'ordine" => "Numero_d_ordine",
      "Numero d'opera" => "Numero_d_opera",
      "116_Tecnica disegni" => "116_Tecnica_disegni",
      "116_Tecnica stampa" => "116_Tecnica_stampa",
      "116_Funzione" => "116_Funzione",
      "116_Specifica del materiale" => "116_Specifica_del_materiale",
      "116_Supporto primario" => "116_Supporto_primario",
      "116_Colore" => "116_Colore",
      "Prima rappresentazione" => "Prima_rappresentazione",
      "120_Indicatore di colore" => "120_Indicatore_di_colore",
      "121_Supporto fisico" => "121_Supporto_fisico",
    );
  }

  public function getFieldsAuthority()
  {
    return array(
      "Identifier" => "identifier",
      "Identificativo di versione" => "idVersion",
      "Unimarc" => "unimarc",
      "Data" => "date",
      "Dati per la elaborazione" => "elaborationData",
      "Nome di persona" => "personalName",
      "Fonte del record" => "sourceRecord",
      "Datazione" => "dating",
      "Nota informativa" => "informativeNote",
      "Fonte bibliografica (esito positivo)" => "sourceBibliographyPositive",
      "Fonte bibliografica (esito negativo)" => "sourceBibliographyNegative",
      "Nome di gruppo (ente)" => "groupName",
      "Regole di catalogazione" => "catalogingRules",
      "Note del catalogatore" => "cataloguerNotes",
      "Forme varianti" => "variantForms",
      "ISADN" => "ISADN",
      "Lingua" => "language",
      "Nazionalita'" => "nationality",
      "Forme varianti (ente)" => "variantFormsEntity",
      "Vedi anche di autore personale" => "seeAlsoAuthor",
      "Vedi anche di gruppo (ente)" => "seeAlsoGroup",
      "Identificativo VID" => "idVID",
      "Nascita" => "birth",
      "Morte" => "death",
      "Tipo di scheda" => "tipoScheda",
      "Tipo di ente" => "tipoEnte",
      "Vedi anche" => "vediAnche",
      "Nome" => "nome",
    );
  }

  public function getArrayCampi()
  {
    return array(
      "Abstract" => 1,
      "Altra edizione su stesso supporto" => 1,
      "Altri numeri standard" => 4,
      "Altri titolo correlati" => 3,
      "Autore" => 3,
      "Autore [sintetico]" => 1,
      "Autore secondario" => 106,
      "Classificazione Dewey" => 5,
      "Codice Dewey" => 5,
      "Codice identificativo" => 1,
      "Collezione" => 4,
      "Composizione (mat. musicale)" => 1,
      "Continua con" => 3,
      "Continuazione di" => 5,
      "Continuazione in parte di" => 5,
      "Data" => 1,
      "Data pubblicazione" => 1,
      "Datazione" => 2,
      "Dati codificati per titolo uniforme musicale" => 1,
      "Dati codificati: contenuto caratteristico dell'espressione" => 1,
      "Dati codificati: elaborazioni musicali (elaborazione)" => 1,
      "Dati codificati: materiale antico" => 1,
      "Dati codificati: materiale cartografico - caratteristiche fisiche" => 1,
      "Dati codificati: materiale cartografico - dati generali" => 1,
      "Dati codificati: materiale grafico" => 1,
      "Dati codificati: monografie" => 1,
      "Dati codificati: musica a stampa - designazione specifica del materiale" => 1,
      "Dati codificati: perodici" => 1,
      "Dati codificati: tipo di supporto" => 1,
      "Dati per la elaborazione" => 1,
      "Descrizione Dewey" => 5,
      "Descrizione fisica" => 1,
      "Descrizioni delle serie / collezioni" => 4,
      "Editore" => 1,
      "Edizione" => 1,
      "Fuso con" => 3,
      "ISBN" => 5,
      "ISMN" => 1,
      "ISSN" => 3,
      "Identificativo di versione" => 1,
      "Impronta" => 8,
      "Inventari e collocazioni delle copie" => 2,
      "Inventario" => 52,
      "IsPartOf" => 1,
      "Istituzione" => 1,
      "Legame a livelli intermedi (subset)" => 2,
      "Legame al livello piu' elevato (set)" => 4,
      "Legame allo spoglio" => 93,
      "Legame parte analitica - padre" => 514,
      "Lingua" => 3,
      "Livello bibliografico" => 1,
      "Localizzazione" => 3,
      "Localizzazione delle copie in formato elettronico" => 1,
      "Luogo" => 2,
      "Luogo di pubblicazione normalizzato" => 4,
      "Marca editoriale" => 6,
      "Nome di gruppo(ente): responsabilita' alternativa" => 2,
      "Nome di gruppo(ente): responsabilita' principale" => 1,
      "Nome di gruppo(ente): responsabilita' secondaria" => 15,
      "Nome di gruppo: forma non accettata" => 41,
      "Nome di persona: forma non accettata" => 40,
      "Nome di persona: responsabilita' alternativa" => 3,
      "Nome di persona: responsabilita' principale" => 2,
      "Nome di persona: responsabilita' secondaria" => 105,
      "Nota informativa" => 15,
      "Note di contenuto" => 1,
      "Note generali" => 15,
      "Note relative all'esemplare" => 15,
      "Note relative alla periodicita' di pubblicazione" => 1,
      "Note relative alla provenienza" => 15,
      "Note relative alla responsabilita'" => 8,
      "Note sulla risorsa elettronica" => 1,
      "Note tra titoli" => 14,
      "Numerazione" => 1,
      "Numero Bibliografica Nazionale" => 9,
      "Numero di monografie e spogli collegati" => 1,
      "Numero editoriale (musica)" => 2,
      "Numero internazionale articolo (ean)" => 1,
      "Orgine del record" => 1,
      "Paese" => 1,
      "Personaggi e interpreti (mat. musicale)" => 1,
      "Presentazione" => 1,
      "Pubblicazione" => 1,
      "Rappresentazione (mat. musicale)" => 1,
      "Responsabilita' principale" => 3,
      "Risorsa elettronica url" => 7,
      "Si scinde in" => 4,
      "Soggetto" => 23,
      "Tipo documento" => 1,
      "Titolo" => 1,
      "Titolo [sintetica]" => 1,
      "Titolo alternativo" => 42,
      "Titolo chiave" => 1,
      "Titolo di raccolta fattizia" => 1,
      "Titolo parallelo" => 5,
      "Titolo uniforme" => 10,
      "Traduzione di" => 7,
      "Unimarc" => 1,
      "hasParts" => 1,
      "Risorsa elettronica" => 1,
      "Inventari e collocazioni delle copie (BE)" => 2,
      "Titolo [sintetico]" => 1,
      "Tipo di mediazione" => 2,
      "Data_inizio" => 2,
      "Data_range" => 2,
      "181_Forma del contenuto" => 2,
      "181_Tipo di contenuto" => 2,
      "181_Movimento" => 2,
      "181_Dimensionalita'" => 2,
      "181_Sensorialita'" => 2,
      "Titoli collegati" => 2,
      "Luogo 2" => 2,
      "Variante del titolo" => 2,
      "Autore (intestazione)" => 2,
      "Collezione [Faccetta]" => 2,
      "Stampatore" => 2,
      "Editore 2" => 2,
      "Data_fine" => 2,
      "Stampatore 2" => 2,
      "ISBN2" => 2,
      "Titolo uniforme, nome" => 2,
      "Titolo uniforme 2" => 2,
      "140_Illustrazioni" => 2,
      "140_Illustrazioni a tutta pagina" => 2,
      "140_Codice letterario" => 2,
      "140_Codice per la pubblicazione" => 2,
      "140_Non usato" => 2,
      "Numero MID" => 2,
      "ISSN2" => 2,
      "105_Tipo testo letterario" => 2,
      "140_Forma del contenuto" => 2,
      "125_Codice presentazione" => 2,
      "125_Indicatore di parti" => 2,
      "128_Organico sintetico" => 2,
      "Organico sintetico" => 2,
      "929_Datazione della composizione" => 2,
      "929_Sezioni" => 2,
      "929_Titolo di ordinamento" => 2,
      "929_Identificativo del titolo uniforme collegato" => 2,
      "Organico analitico" => 2,
      "ISMN2" => 2,
      "929_Numero di catalogo tematico" => 2,
      "Numero catalogo tematico" => 2,
      "ISMN2" => 2,
      "Personaggi" => 2,
      "Sartori" => 2,
      "929_Numero d'ordine" => 2,
      "929_Numero d'opera" => 2,
      "929_Tonalita' della composizione" => 2,
      "Numero d'ordine" => 2,
      "Numero d'opera" => 2,
      "116_Tecnica disegni" => 2,
      "116_Tecnica stampa" => 2,
      "116_Funzione" => 2,
      "116_Specifica del materiale" => 2,
      "116_Supporto primario" => 2,
      "116_Colore" => 2,
      "Prima rappresentazione" => 2,
      "120_Indicatore di colore" => 2,
      "121_Supporto fisico" => 2,
    );
  }

  private function lowerArray($array)
  {
    $appoggio = array();
    if (is_array($array)) {
      foreach ($array as $v) {
        array_push($appoggio, strtolower($v));
      }
    } else {
      $appoggio = strtolower($array);
    }
    return $appoggio;

  }
}
