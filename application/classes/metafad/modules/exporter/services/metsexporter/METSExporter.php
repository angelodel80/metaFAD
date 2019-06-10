<?php
set_time_limit(0);
ini_set('memory_limit', '2048M');
//__Paths::addClassSearchPath("./classes/");

class metafad_modules_exporter_services_metsexporter_METSExporter extends GlizyObject
{
  private $countU = 1;  
  private $dam;

  function METSExport($UAD, $instance, $nomefile)
  {

    $dirRead = glz_findClassPath("metafad/modules/exporter/services/metsexporter/input", false) . "/";
    $dirWrite = "./export/";
    $this->dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instance);

    switch ($instance) {
      case "societa-napoletana-di-storia-patria":
        $instance = "SNSP";
        break;
      case "cappella-del-tesoro-di-san-gennaro":
        $instance = "CTSG";
        break;
      case "fondazione-biblioteca-benedetto-croce":
        $instance = "FBBC";
        break;
      case "istituto-italiano-per-gli-studi-storici":
        $instance = "IISS";
        break;
      case "pio-monte-della-misericordia":
        $instance = "PMMI";
        break;
    }

    $docWrite = new DOMDocument();
    $docWrite->formatOutput = true;
    $docWrite->load($dirRead . "METS-no-rec.xml");

    $wXpath = new DOMXPath($docWrite);

    $wQuery = "/envelope:envelope/envelope:header/@CREATED";
    $nodo = $wXpath->query($wQuery);
    $nodo->item(0)->nodeValue = date('Y-m-d') . "T" . date('h:i:s') . ".000+01:00";

    $wQuery = "/envelope:envelope/envelope:header/envelope:source";
    $nodo = $wXpath->query($wQuery);
    $nodo->item(0)->nodeValue = "Export-MetaFAD";

    $wQuery = "/envelope:envelope/envelope:recordList";
    $nodoRacc = $wXpath->query($wQuery);

    $recUA = org_glizy_ObjectFactory::createModel('archivi.models.UnitaArchivistica');
    $recUD = org_glizy_ObjectFactory::createModel('archivi.models.UnitaDocumentaria');
    $recAntro = org_glizy_ObjectFactory::createModel('archivi.models.Antroponimi');
    $recEnti = org_glizy_ObjectFactory::createModel('archivi.models.Enti');
    $recTopon = org_glizy_ObjectFactory::createModel('archivi.models.Toponimi');

    foreach ($UAD as $idUAD) {
      $add = true;
      $docRead = new DOMDocument();
      $docRead->load($dirRead . "METS-1-rec.xml");

      $rXpath = new DOMXPath($docRead);

      $rQuery = "/envelope:envelope/envelope:recordList/envelope:record";
      $nodoRec = $rXpath->query($rQuery);

      $docType = org_glizy_ObjectFactory::createModelIterator(
        'metafad.modules.exporter.models.Scheda',
        'docType',
        array('params' => array($idUAD))
      );

      if ($docType->current()->document_type == 'archivi.models.UnitaArchivistica') {

        $recUA->load($idUAD);
        $data = $recUA->getRawData();

        $rQuery = "./envelope:recordHeader/envelope:recordDatestamp";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = date('Y-m-d') . "T" . date('h:i:s') . ".000+01:00";

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@CREATEDATE";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = date('Y-m-d') . "T" . date('h:i:s') . ".000+01:00";

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@LASTMODDATE";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = date('Y-m-d') . "T" . date('h:i:s') . ".000+01:00";

        $rQuery = "./envelope:recordHeader/envelope:recordIdentifier";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitid";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='CREATOR']/mets:name";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $instance;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='IPOWNER']/mets:name";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $instance;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID/@TYPE";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $instance;

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:odd[@type='Segnatura']/ead:p";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->segnaturaAttuale;

        $this->insertOrRemoveNode($data->denominazione, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did",
          "ead:unittitle", true);

        $this->insertOrRemoveNode($data->cronologia[0]->estremoCronologicoTestuale, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did",
          "ead:unitdate", true);

        $this->insertOrRemoveNode($data->denominazione, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc",
          "ead:unittitle", true);

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:phystech/ead:p";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->descrizioneFisica_tipologia;

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc/ead:genreform";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->descrizioneFisica_consistenza[0]->consistenza_tipologia;

        $this->insertOrRemoveNode($data->descrizioneContenuto, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did",
          "ead:abstract");

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        for ($i = 0; $i < count($data->antroponimi); $i++) {

          $recAntro->load($data->antroponimi[$i]->intestazione->id);
          $dataAntro = $recAntro->getRawData();

          $persname = $docRead->createElement('ead:persname', $dataAntro->cognome . ", " . $dataAntro->nome);
          $qualifica = $docRead->createElement('ead:emph', $dataAntro->qualificazione);
          $nodo->item(0)->appendChild($persname);
          $nodo->item(0)->firstChild->appendChild($qualifica);
        }

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        for ($i = 0; $i < count($data->enti); $i++) {

          $recEnti->load($data->enti[$i]->intestazione->id);
          $dataEnti = $recEnti->getRawData();

          if ($dataEnti->denominazioneEnte) 
          {
            $corpname = $docRead->createElement('ead:corpname', $dataEnti->denominazioneEnte);
            $nodo->item(0)->appendChild($corpname);
          }
        }

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        for ($i = 0; $i < count($data->toponimi); $i++) {

          $recTopon->load($data->toponimi[$i]->intestazione->id);
          $dataTopon = $recTopon->getRawData();

          if($dataTopon->nomeLuogo)
          {
            $geogname = $docRead->createElement('ead:geogname', $dataTopon->nomeLuogo);
            $nodo->item(0)->appendChild($geogname);
          }
        }

        if ($data->mediaCollegati) {
          $varJson = json_decode($data->mediaCollegati, true);
          //REFERENCE & THUMBNAIL
          $this->createNodeForImages($docRead, $nodoRec, $rXpath, $varJson['id'], array($varJson["metadata"], $varJson['thumbnail']), array('reference image', 'thumbnail image'));
        }
        else if ($data->linkedStruMag) {
          if ($data->linkedStruMag->id) {
            $images = $this->getStrumagImages($data->linkedStruMag->id);
            if(!empty($images))
            {
              foreach ($images as $i) {
                //REFERENCE & THUMBNAIL
                $this->createNodeForImages($docRead, $nodoRec, $rXpath, $i['id'], array($i["metadata"], $i['thumbnail']), array('reference image', 'thumbnail image'));
                break;
              }
            } else {
              $add = false;
            }
          } 
          else 
          {
            $add = false;
          }
        }

      } elseif ($docType->current()->document_type == 'archivi.models.UnitaDocumentaria') {

        $recUD->load($idUAD);
        $data = $recUD->getRawData();

        $rQuery = "./envelope:recordHeader/envelope:recordDatestamp";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = date('Y-m-d') . "T" . date('h:i:s') . ".000+01:00";

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@CREATEDATE";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = date('Y-m-d') . "T" . date('h:i:s') . ".000+01:00";

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@LASTMODDATE";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = date('Y-m-d') . "T" . date('h:i:s') . ".000+01:00";

        $rQuery = "./envelope:recordHeader/envelope:recordIdentifier";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitid";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->codiceIdentificativoSistema;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='CREATOR']/mets:name";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $instance;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='IPOWNER']/mets:name";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $instance;

        $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID/@TYPE";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $instance;

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:odd[@type='Segnatura']/ead:p";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->segnaturaAttuale;

        $this->insertOrRemoveNode($data->denominazione, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did",
          "ead:unittitle", true);

        $this->insertOrRemoveNode($data->cronologia[0]->estremoCronologicoTestuale, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did",
          "ead:unitdate", true);

        $this->insertOrRemoveNode($data->denominazione, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc",
          "ead:unittitle", true);

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:phystech/ead:p";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->descrizioneFisica_tipologia;

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc/ead:genreform";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        $nodo->item(0)->nodeValue = $data->consistenza[0]->descrizioneFisicaSupporto_tipologia;

        $this->insertOrRemoveNode($data->contestoProvenienza_descrizione, $nodoRec, $rXpath,
          "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did",
          "ead:abstract");

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        for ($i = 0; $i < count($data->antroponimi); $i++) {

          $recAntro->load($data->antroponimi[$i]->intestazione->id);
          $dataAntro = $recAntro->getRawData();

          $persname = $docRead->createElement('ead:persname', $dataAntro->cognome . ", " . $dataAntro->nome);
          $qualifica = $docRead->createElement('ead:emph', $dataAntro->qualificazione);
          $nodo->item(0)->appendChild($persname);
          $nodo->item(0)->firstChild->appendChild($qualifica);
        }

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        for ($i = 0; $i < count($data->enti); $i++) {

          $recEnti->load($data->enti[$i]->intestazione->id);
          $dataEnti = $recEnti->getRawData();
          if($dataEnti->denominazioneEnte)
          {
            $corpname = $docRead->createElement('ead:corpname', $dataEnti->denominazioneEnte);
            $nodo->item(0)->appendChild($corpname);
          }
        }

        $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
        $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
        for ($i = 0; $i < count($data->toponimi); $i++) {

          $recTopon->load($data->toponimi[$i]->intestazione->id);
          $dataTopon = $recTopon->getRawData();

          if($dataTopon->nomeLuogo)
          {
            $geogname = $docRead->createElement('ead:geogname', $dataTopon->nomeLuogo);
            $nodo->item(0)->appendChild($geogname);
          }
        }

        if ($data->mediaCollegati) {
          $varJson = json_decode($data->mediaCollegati, true);
          //REFERENCE & THUMBNAIL
          $this->createNodeForImages($docRead, $nodoRec, $rXpath, $varJson['id'], array($varJson["metadata"], $varJson['thumbnail']) , array('reference image', 'thumbnail image'));
        }
        else if ($data->linkedStruMag) {
          if($data->linkedStruMag->id)
          {
            $images = $this->getStrumagImages($data->linkedStruMag->id);
            if(!empty($images))
            {
              foreach ($images as $i) {
                //REFERENCE & THUMBNAIL
                $this->createNodeForImages($docRead, $nodoRec, $rXpath, $i['id'], array($i["metadata"], $i['thumbnail']), array('reference image', 'thumbnail image'));
                break;  
              }
            } else {
              $add = false;
            }
          }
          else
          {
            $add = false;
          }
        }
      } else {
        echo "Unità con id $idUAD non riconosciuta";
      }

      $rQuery = "./envelope:recordBody/mets:mets/mets:amdSec/mets:rightsMD[@ID='amdRD001']/mets:mdWrap/mets:xmlData/metsrights:RightsDeclarationMD/metsrights:RightsHolder/metsrights:RightsHolderName";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = $instance;

      $rQuery = "./envelope:recordBody/mets:mets/mets:amdSec/mets:rightsMD[@ID='amdRA001']/mets:mdWrap/mets:xmlData/metsrights:RightsDeclarationMD/metsrights:RightsHolder/metsrights:RightsHolderName";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = $instance;

      if($add)
      {
        $recImport = $docWrite->importNode($nodoRec->item(0), true);
        $nodoRacc->item(0)->appendChild($recImport);
      }

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $this->deleteChildren($nodo->item(0));

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:odd[@type='Segnatura']/ead:p";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = '';

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitdate";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = '';

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:phystech/ead:p";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = '';

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc/ead:genreform";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = '';

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:abstract";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = '';

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-desc-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:langmaterial/ead:language/@langcode";
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = '';

      $this->countU++;
    }

    echo "<br>" . $docWrite->save($dirWrite . $nomefile . ".xml");
  }

  function deleteChildren($node)
  {
    while (isset($node->firstChild)) {
      $this->deleteChildren($node->firstChild);
      $node->removeChild($node->firstChild);
    }
  }

  function createNodeForImages(&$docRead, &$nodoRec, &$rXpath, $id, $href, $use)
  {
    $rQuery = "./envelope:recordBody/mets:mets";
    $mets = $rXpath->query($rQuery, $nodoRec->item(0));
    $fileSec = $docRead->createElement("mets:fileSec");
    $fileSec = $mets->item(0)->appendChild($fileSec);

    foreach($href as $k => $h)
    {
      $fileGrp = $docRead->createElement("mets:fileGrp");
      $child = $fileSec->appendChild($fileGrp);

      $child->setAttribute('USE', $use[$k]);

      $file = $docRead->createElement("mets:file");
      $child = $child->appendChild($file);

      $child->setAttribute('ID', 'ID-'.$id);
      $child->setAttribute('MIMETYPE', 'image/jpeg');

      $fLocat = $docRead->createElement("mets:FLocat");
      $child = $child->appendChild($fLocat);

      $child->setAttribute('LOCTYPE','URL');
      $child->setAttribute('xlink:href', $h);
    }
  }

  function insertOrRemoveNode($field, &$nodoRec, &$rXpath, $parentQuery, $child, $compulsory = false)
  {
    if ($field) {
      $rQuery = $parentQuery.'/'.$child;
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = $field;
    }
    else if($compulsory) {
      $rQuery = $parentQuery.'/'.$child;
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));
      $nodo->item(0)->nodeValue = 'N.D.';
    }
    else {
      $rQuery = $parentQuery;
      $nodo = $rXpath->query($rQuery, $nodoRec->item(0));

      $rQuery = "./".$child;
      $figlio = $rXpath->query($rQuery, $nodo->item(0));

      $nodo->item(0)->removeChild($figlio->item(0));
    }
  }

  function getStrumagImages($id)
  {
    $imagesList = array();
    $strumag = __ObjectFactory::createModel('metafad.teca.STRUMAG.models.Model');
    $strumag->load($id);
    $ps = json_decode($strumag->physicalSTRU);
    $images = $ps->image;
    if(!empty($images))
    {
      foreach($images as $i)
      {
        $imagesList[] = array('id'=>$i->id, 'metadata' => $this->dam->mediaUrl($i->id, false), 'thumbnail' => $this->dam->streamUrl($i->id,'thumbnail'));
      }
    }
    return $imagesList;
  }

}
