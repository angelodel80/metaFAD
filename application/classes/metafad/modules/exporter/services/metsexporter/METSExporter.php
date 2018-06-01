<?php
set_time_limit(0);
ini_set('memory_limit', '2048M');
//__Paths::addClassSearchPath("./classes/");

class metafad_modules_exporter_services_metsexporter_METSExporter extends GlizyObject{

function METSExport($UAD, $instance, $nomefile){

  $dirRead = org_glizy_Paths::get('ROOT') . "application/classes/metafad/modules/exporter/services/metsexporter/input/";
  $dirWrite=org_glizy_Paths::get('ROOT').'export/';;

  switch ($instance) {
    case "societa-napoletana-di-storia-patria":
        $instance="SNSP";
        break;
    case "cappella-del-tesoro-di-san-gennaro":
        $instance="CTSG";
        break;
    case "fondazione-biblioteca-benedetto-croce":
        $instance="FBBC";
        break;
    case "istituto-italiano-per-gli-studi-storici":
        $instance="IISS";
        break;
    case "pio-monte-della-misericordia":
        $instance="PMMI";
        break;
  }

  $docRead = new DOMDocument();
  $docRead->load($dirRead."METS-1-rec.xml");

  $rXpath = new DOMXPath($docRead);

  $rQuery = "/envelope:envelope/envelope:recordList/envelope:record";
  $nodoRec=$rXpath->query($rQuery);

  var_dump($nodoRec->item(0)->nodeName);

  $docWrite = new DOMDocument();
  $docWrite->formatOutput = true;
  $docWrite->load($dirRead."METS-no-rec.xml");

  $wXpath = new DOMXPath($docWrite);

  $wQuery = "/envelope:envelope/envelope:header/@CREATED";
  $nodo=$wXpath->query($wQuery);
  $nodo->item(0)->nodeValue=date('Y-m-d')."T".date('h:i:s').".000+01:00";

  $wQuery = "/envelope:envelope/envelope:header/envelope:source";
  $nodo=$wXpath->query($wQuery);
  $nodo->item(0)->nodeValue="Export MetaFAD";

  $wQuery = "/envelope:envelope/envelope:recordList";
  $nodoRacc=$wXpath->query($wQuery);

  var_dump($nodoRacc->item(0)->nodeName);

  $recUA = org_glizy_ObjectFactory::createModel('archivi.models.UnitaArchivistica');
  $recUD = org_glizy_ObjectFactory::createModel('archivi.models.UnitaDocumentaria');
  $recAntro = org_glizy_ObjectFactory::createModel('archivi.models.Antroponimi');
  $recEnti = org_glizy_ObjectFactory::createModel('archivi.models.Enti');
  $recTopon = org_glizy_ObjectFactory::createModel('archivi.models.Toponimi');

  foreach($UAD as $idUAD){

    $docType= org_glizy_ObjectFactory::createModelIterator (
      'metafad.modules.exporter.models.Scheda', 'docType',
      array ('params' => array ($idUAD))
    );

    var_dump($docType->current()->document_type);

    if($docType->current()->document_type=='archivi.models.UnitaArchivistica'){

      $recUA->load($idUAD);
      $data = $recUA->getRawData();
      var_dump($data->codiceIdentificativoSistema);

      $attr=$docRead->createAttribute('OBJID');
      $attr->value=$data->codiceIdentificativoSistema;
      $rQuery = "./envelope:recordBody/mets:mets";
      $nodoRootMets=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodoRootMets->item(0)->appendChild($attr);

      $rQuery = "./envelope:recordHeader/envelope:recordDatestamp";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=date('Y-m-d')."T".date('h:i:s').".000+01:00";

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@CREATEDATE";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=date('Y-m-d')."T".date('h:i:s').".000+01:00";

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@LASTMODDATE";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=date('Y-m-d')."T".date('h:i:s').".000+01:00";

      $rQuery = "./envelope:recordHeader/envelope:recordIdentifier";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitid";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='reference image']/mets:file/@ID";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='thumbnail image']/mets:file/@ID";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='CREATOR']";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$instance;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='IPOWNER']";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$instance;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID/@TYPE";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$instance;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:odd[@type='Segnatura']";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->segnaturaAttuale;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unittitle";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->denominazione;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitdate";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->cronologia[0]->estremoCronologicoTestuale;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:phystech/ead:p";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->descrizioneFisica_tipologia;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc/ead:genreform";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->descrizioneFisica_consistenza[0]->consistenza_tipologia;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:abstract";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->descrizioneContenuto;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:langmaterial/ead:language/@langcode";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->linguaDescrizioneRecord;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      for($i=0;$i<count($data->antroponimi);$i++){

        $recAntro->load($data->antroponimi[$i]->intestazione->id);
        $dataAntro = $recAntro->getRawData();

        $persname=$docRead->createElement('ead:persname',$dataAntro->cognome.", ".$dataAntro->nome);
        $qualifica=$docRead->createElement('ead:emph',$dataAntro->qualificazione);
        $nodo->item(0)->appendChild($persname);
        $nodo->item(0)->firstChild->appendChild($qualifica);
      }

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      for($i=0;$i<count($data->enti);$i++){

        $recEnti->load($data->enti[$i]->intestazione->id);
        $dataEnti = $recEnti->getRawData();

        $corpname=$docRead->createElement('ead:corpname',$dataEnti->denominazioneEnte);
        $nodo->item(0)->appendChild($corpname);
      }

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      for($i=0;$i<count($data->toponimi);$i++){

        $recTopon->load($data->toponimi[$i]->intestazione->id);
        $dataTopon = $recTopon->getRawData();

        $geogname=$docRead->createElement('ead:geogname',$dataTopon->nomeLuogo);
        $nodo->item(0)->appendChild($geogname);
      }

      $varJson=json_decode($data->mediaCollegati, true);
      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='reference image']/mets:file/mets:FLocat/@xlink:href";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$varJson["metadata"];

      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='thumbnail image']/mets:file/mets:FLocat/@xlink:href";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$varJson["thumbnail"];

    }elseif($docType->current()->document_type=='archivi.models.UnitaDocumentaria'){

      $recUD->load($idUAD);
      $data = $recUD->getRawData();
      var_dump($data->codiceIdentificativoSistema);

      $attr=$docRead->createAttribute('OBJID');
      $attr->value=$data->codiceIdentificativoSistema;
      $rQuery = "./envelope:recordBody/mets:mets";
      $nodoRootMets=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodoRootMets->item(0)->appendChild($attr);

      $rQuery = "./envelope:recordHeader/envelope:recordDatestamp";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=date('Y-m-d')."T".date('h:i:s').".000+01:00";

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@CREATEDATE";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=date('Y-m-d')."T".date('h:i:s').".000+01:00";

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/@LASTMODDATE";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=date('Y-m-d')."T".date('h:i:s').".000+01:00";

      $rQuery = "./envelope:recordHeader/envelope:recordIdentifier";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitid";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='reference image']/mets:file/@ID";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='thumbnail image']/mets:file/@ID";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->codiceIdentificativoSistema;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='CREATOR']";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$instance;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:agent[@ROLE='IPOWNER']";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$instance;

      $rQuery = "./envelope:recordBody/mets:mets/mets:metsHdr/mets:altRecordID/@TYPE";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$instance;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:odd[@type='Segnatura']";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->segnaturaAttuale;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unittitle";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->denominazione;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitdate";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->cronologia[0]->estremoCronologicoTestuale;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:phystech/ead:p";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->descrizioneFisica_tipologia;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc/ead:genreform";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->consistenza[0]->descrizioneFisicaSupporto_tipologia;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:abstract";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->contestoProvenienza_descrizione;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:langmaterial/ead:language/@langcode";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$data->linguaDescrizioneRecord;

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      for($i=0;$i<count($data->antroponimi);$i++){

        $recAntro->load($data->antroponimi[$i]->intestazione->id);
        $dataAntro = $recAntro->getRawData();

        $persname=$docRead->createElement('ead:persname',$dataAntro->cognome.", ".$dataAntro->nome);
        $qualifica=$docRead->createElement('ead:emph',$dataAntro->qualificazione);
        $nodo->item(0)->appendChild($persname);
        $nodo->item(0)->firstChild->appendChild($qualifica);
      }

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      for($i=0;$i<count($data->enti);$i++){

        $recEnti->load($data->enti[$i]->intestazione->id);
        $dataEnti = $recEnti->getRawData();

        $corpname=$docRead->createElement('ead:corpname',$dataEnti->denominazioneEnte);
        $nodo->item(0)->appendChild($corpname);
      }

      $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      for($i=0;$i<count($data->toponimi);$i++){

        $recTopon->load($data->toponimi[$i]->intestazione->id);
        $dataTopon = $recTopon->getRawData();

        $geogname=$docRead->createElement('ead:geogname',$dataTopon->nomeLuogo);
        $nodo->item(0)->appendChild($geogname);
      }

      $varJson=json_decode($data->mediaCollegati, true);
      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='reference image']/mets:file/mets:FLocat/@xlink:href";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$varJson["metadata"];

      $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='thumbnail image']/mets:file/mets:FLocat/@xlink:href";
      $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
      $nodo->item(0)->nodeValue=$varJson["thumbnail"];

    }else{
      echo "Unità con id $idUAD non riconosciuta";
    }

    $rQuery = "./envelope:recordBody/mets:mets/mets:amdSec/mets:rightsMD[@ID='amdRD001']/mets:mdWrap/mets:xmlData/metsrights:RightsDeclarationMD/metsrights:RightsHolder/metsrights:RightsHolderName";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue=$instance;

    $rQuery = "./envelope:recordBody/mets:mets/mets:amdSec/mets:rightsMD[@ID='amdRA001']/mets:mdWrap/mets:xmlData/metsrights:RightsDeclarationMD/metsrights:RightsHolder/metsrights:RightsHolderName";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue=$instance;

    $recImport = $docWrite->importNode($nodoRec->item(0), true);
    $nodoRacc->item(0)->appendChild($recImport);

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:controlaccess";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $this->deleteChildren($nodo->item(0));

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:odd[@type='Segnatura']";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unittitle";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:unitdate";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:phystech/ead:p";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:physdesc/ead:genreform";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:abstract";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:dmdSec[@ID='ead-des-001']/mets:mdWrap/mets:xmlData/ead:c/ead:did/ead:langmaterial/ead:language/@langcode";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='reference image']/mets:file/mets:FLocat/@xlink:href";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

    $rQuery = "./envelope:recordBody/mets:mets/mets:fileSec/mets:fileGrp[@USE='thumbnail image']/mets:file/mets:FLocat/@xlink:href";
    $nodo=$rXpath->query($rQuery,$nodoRec->item(0));
    $nodo->item(0)->nodeValue='';

  }

  echo "<br>".$docWrite->save($dirWrite.$nomefile.".xml");
}

function deleteChildren($node) {
    while (isset($node->firstChild)) {
        $this->deleteChildren($node->firstChild);
        $node->removeChild($node->firstChild);
    }
}

}
