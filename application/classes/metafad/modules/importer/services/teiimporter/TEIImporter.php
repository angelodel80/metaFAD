<?php
class metafad_modules_importer_services_teiimporter_TEIImporter extends GlizyObject{

function importTEI($dirRead,$instituteKey){

  metafad_usersAndPermissions_Common::setInstituteKey($instituteKey);

  $filesManos=scandir($dirRead);
  foreach ($filesManos as $fileMano) {
    if (strtolower(pathinfo($fileMano, PATHINFO_EXTENSION)) == 'xml'){

      $docRead = new DOMDocument();

      $fileName=$dirRead."/".$fileMano;
      $docRead->load($fileName);

      echo "<br>>>>>>$fileName";

      $rXpath = new DOMXPath($docRead);
      $rXpath->registerNamespace("x", 'http://www.tei-c.org/ns/1.0');

      $rQuery = "/x:TEI/x:text/x:body/x:listBibl/x:msDesc/x:physDesc/x:p/x:term[@n='1']";
      $nodoUniCom=$rXpath->query($rQuery);

      //Unitario o Composito
      //echo "*".count($nodoUniCom);
      echo "<br>*".$nodoUniCom->item(0)->nodeValue;

      $rQuery = "/x:TEI/x:text/x:body/x:listBibl/x:msDesc";
      $nodoManoscr=$rXpath->query($rQuery);
      echo "<br>***".$nodoManoscr->item(0)->getAttribute('xml:id');

      $rQuery = "/x:TEI/x:text/x:body/x:listBibl/x:msDesc/x:physDesc/x:p/x:term[@n='2']";
      $nodoFasc=$rXpath->query($rQuery);
      echo "<br>+++".$nodoFasc->item(0)->nodeValue;
      if($nodoFasc->item(0)->nodeValue=="Fascicoli legati"){
        $nodoManoscr->item(0)->setAttribute('fascicoli', true);
      }else{
        $nodoManoscr->item(0)->setAttribute('fascicoli', false);
      }

      $rQuery = "/x:TEI/x:text/x:body/x:listBibl/x:msDesc/x:physDesc/x:decoDesc/x:decoNote[@subtype='semplici']";
      $nodoNoteIni=$rXpath->query($rQuery);
      echo "<br>+++".$nodoNoteIni->item(0)->nodeValue;
      if($nodoNoteIni->item(0)->nodeValue=="semplici"){
        $nodoManoscr->item(0)->setAttribute('noteinizialisempl', true);
      }else{
        $nodoManoscr->item(0)->setAttribute('noteinizialisempl', false);
      }

      if($nodoUniCom->item(0)->nodeValue=="Unitario"){

        $nodoManoscr->item(0)->setAttribute('sectiontype','manoscritto-unitario');
        $retInsManoscritto=$this->insManoscritto($nodoManoscr->item(0));
        echo "<br>@@@".$retInsManoscritto->id[set][__id];

        $rQuery = "/x:TEI/x:text/x:body/x:listBibl/x:msDesc/x:msContents/x:msItem";
        $nodiUT=$rXpath->query($rQuery);
        foreach ($nodiUT as $nodoUT) {
          echo "<br>*".$nodoUT->getAttribute('n');
          $nodoUT->setAttribute('idparent',$retInsManoscritto->id[set][__id]);
          $nodoUT->setAttribute('textparent',$retInsManoscritto->id[set][text]);
          $retInsUT=$this->insUT($nodoUT);
        }

      }elseif($nodoUniCom->item(0)->nodeValue=="Composito"){

        $nodoManoscr->item(0)->setAttribute('sectiontype','manoscritto-composito');
        $retInsManoscritto=$this->insManoscritto($nodoManoscr->item(0));
        echo "<br>@@@".$retInsManoscritto->id[set][__id];

        $rQuery = "/x:TEI/x:text/x:body/x:listBibl/x:msDesc/x:msPart";
        $nodiUC=$rXpath->query($rQuery);
        foreach ($nodiUC as $nodoUC) {
          echo "<br>*".$nodoUC->getAttribute('n');
          $nodoUC->setAttribute('idparent',$retInsManoscritto->id[set][__id]);
          $nodoUC->setAttribute('textparent',$retInsManoscritto->id[set][text]);
          $retInsUC=$this->insUC($nodoUC);

          $nodoQuery = "//x:msPart[@n='".$nodoUC->getAttribute('n')."']/x:msContents/x:msItem";
          $nodiUT=$rXpath->query($nodoQuery);
          foreach ($nodiUT as $nodoUT) {
            echo "<br>*".$nodoUT->getAttribute('n');
            $nodoUT->setAttribute('idparent',$retInsUC->id[set][__id]);
            $nodoUT->setAttribute('textparent',$retInsUC->id[set][text]);
            $retInsUT=$this->insUT($nodoUT);
          }
        }

      }else{
        echo "Errore nel file: impossibile determinare se il manoscritto è Unitario o Composito";
      }
    }
  }
}

function insManoscritto($nodoManoscr){
  $params = new StdClass();
  $params->schemafile=org_glizy_Paths::get('ROOT')."application/classes/metafad/common/importer/jsonSchemas/TEI/manoscritto_schema.json";

  $input= new StdClass();
  $input->domElement=$nodoManoscr;

  $mainRunner=org_glizy_ObjectFactory::createObject("metafad.common.importer.MainRunner");
  $XmlToJson=org_glizy_ObjectFactory::createObject("metafad.common.importer.operations.XmlToJson", $params, $mainRunner);
  $retXmlToJson=$XmlToJson->execute($input, array('x' => 'http://www.tei-c.org/ns/1.0'));

  $SaveTEI=org_glizy_ObjectFactory::createObject("metafad.common.importer.operations.SaveTEI", $params, $mainRunner);
  $retSaveTEI=$SaveTEI->execute($retXmlToJson);

  return $retSaveTEI;
}

function insUT($nodoUT){
  $params = new StdClass();
  $params->schemafile=org_glizy_Paths::get('ROOT')."application/classes/metafad/common/importer/jsonSchemas/TEI/unitaTestuale_schema.json";

  $input= new StdClass();
  $input->domElement=$nodoUT;

  $mainRunner=org_glizy_ObjectFactory::createObject("metafad.common.importer.MainRunner");
  $XmlToJson=org_glizy_ObjectFactory::createObject("metafad.common.importer.operations.XmlToJson", $params, $mainRunner);
  $retXmlToJson=$XmlToJson->execute($input, array('x' => 'http://www.tei-c.org/ns/1.0'));

  $SaveTEI=org_glizy_ObjectFactory::createObject("metafad.common.importer.operations.SaveTEI", $params, $mainRunner);
  $retSaveTEI=$SaveTEI->execute($retXmlToJson);

  return $retSaveTEI;
}

function insUC($nodoUC){
  $params = new StdClass();
  $params->schemafile=org_glizy_Paths::get('ROOT')."application/classes/metafad/common/importer/jsonSchemas/TEI/unitaCodicologica_schema.json";

  $input= new StdClass();
  $input->domElement=$nodoUC;

  $mainRunner=org_glizy_ObjectFactory::createObject("metafad.common.importer.MainRunner");
  $XmlToJson=org_glizy_ObjectFactory::createObject("metafad.common.importer.operations.XmlToJson", $params, $mainRunner);
  $retXmlToJson=$XmlToJson->execute($input, array('x' => 'http://www.tei-c.org/ns/1.0'));

  $SaveTEI=org_glizy_ObjectFactory::createObject("metafad.common.importer.operations.SaveTEI", $params, $mainRunner);
  $retSaveTEI=$SaveTEI->execute($retXmlToJson);

  return $retSaveTEI;
}

}
