<?php
class metafad_modules_exporter_services_xmlexporter_TrcExporterXML extends GlizyObject{

  var $moduleName, $exportPath, $folderName, $TSK, $version, $recAut, $recBib, $currNCTN, $currNCTR, $recImmFTAN, $ESC, $dam, $autbib;

  /**
   * @param $schema stdClass Schema (Elements.json) in formato stdClass
   * @param $data stdClass Dati da cui attingere le informazioni richieste dallo schema
   * @param $current DOMElement Elemento DOM a cui appendere le informazioni da estrarre
   * @param $doc DOMDocument Documento principale (permette la creazione di nodi)
   */
  function appendInformations($schema, $data, $current, $doc)
  {
      foreach ($schema as $campino) {
          $fieldName = $campino->name;
          $max = $campino->maxOccurs;
          $min = $campino->minOccurs;
          $children = property_exists($campino, "children") ? $campino->children : null;
          $hasLink = property_exists($campino, "linkCards") ? $campino->linkCards : false;

          /**
           * Questo equivale al caso del fieldset:
           * si crea un nuovo tag
           * scendo nella gerarchia dello schema
           * NON scendo nella gerarchia dei dati
           * riempo ricorsivamente questo nuovo tag
           */
          if ($max === "1" && $min === "1" && $children) {
              $newNode = $doc->createElement($fieldName);
              $this->appendInformations($campino->children, $data, $newNode, $doc);
              $current->appendChild($newNode); //MZ spostato prima  della ricorsione
          }

          /**
           * Questo caso equivale ad un campo singolo:
           * si crea un nuovo tag
           * lo popolo con il testo contenuto
           */
          else if ($max === "1" && $min === "1") { //Campo "obbligatorio", nel $data ho la proprietà testuale
              if (property_exists($data, $fieldName) && trim($data->{$fieldName})){
                  /*
                  if($fieldName=="NCTR") $this->currNCTR=$data->{$fieldName};
                  if($fieldName=="NCTN") $this->currNCTN=$data->{$fieldName};
                  if($fieldName=="FTAN") $this->recImmFTAN[]=$this->currNCTN."|".$this->currNCTR."|".$data->{$fieldName};
                  */
                  $newNode = $doc->createElement($fieldName);
                  $newNode->nodeValue = trim($data->{$fieldName});
                  $current->appendChild($newNode);
              }
          }

          /**
           * Questo caso equivale ai repeater:
           * si crea un nuovo tag
           * scendo nella gerarchia dello schema
           * scendo nella gerarchia dei dati
           * popolo ricorsivamente il tag per ogni figlio che ho nei dati attuali
           */
          else if ($children) { //Repeater, nel $data ho la proprietà che contiene gli oggetti
              foreach($data->{$fieldName} as $item){
                  $newNode = $doc->createElement($fieldName);
                  $this->appendInformations($campino->children, $item, $newNode, $doc);
                  $current->appendChild($newNode);
              }
          }

          /**
           * Questo caso equivale all'array di stringhe:
           * si crea un nuovo tag
           * NON scendo nella gerarchia dello schema (non ci sono campi figli)
           * popolo il nuovo tag con le informazioni
           */
          else {
              /**
               * Campo ripetibile: creo un tag per ogni ripetizione
               * Repeater di campi testuali, ovvero sono oggetti di tipo «NAME»-element all'interno di $data
               */
              if ($max !== "1"){
                  foreach($data->{$fieldName} as $item){
                      if ($item->{"$fieldName-element"}){
                          $newNode = $doc->createElement($fieldName);
                          $newNode->nodeValue = trim($hasLink ? $item->{"$fieldName-element"}->text : $item->{"$fieldName-element"});
                          $current->appendChild($newNode);
                      }
                  }
              }
              /**
               * Campo non ripetibile (maxOccurs = 1):
               * popolo un singolo file
               * controllo subito se la proprietà esiste, così evito di creare nodi XML vuoti
               */
              else if (property_exists($data, $fieldName)) {
                  $newNode = $doc->createElement($fieldName);
                  if (!is_object($data->{$fieldName}) && trim($data->{$fieldName})){ //Stringa
                      $newNode->nodeValue = trim($data->{$fieldName});
                      $current->appendChild($newNode);
                  } else if (property_exists($data->{$fieldName}, "text") && trim($data->{$fieldName})){ //stdClass Link
                      $newNode->nodeValue = trim($data->{$fieldName}->text);
                      $current->appendChild($newNode);
                  }
              }
          }
      }
  }


  function scanBibAut($data){
      foreach ($data as $element) {
          //var_dump($element);
          if(is_object($element[0])){
            foreach ($element[0] as $key => $value) {
              if($key=="__AUT" && $value!='' && !in_array($value->id, $this->recAut)) $this->recAut[]= $value->id;
              if($key=="__BIB" && $value!='' && !in_array($value->id, $this->recBib)) $this->recBib[]= $value->id;
            }
          }
      }
  }

  function scanFtan($data){
      $this->currNCTR=$data->NCTR;
      $this->currNCTN=$data->NCTN;
      foreach ($data as $key => $value) {
          if($key=='FTA'){
            foreach ($value as $recFtan) {
              $img = json_decode($recFtan->{'FTA-image'});
              $this->recImmFTAN[]=array("NCTR"=>$this->currNCTR, "NCTN"=>$this->currNCTN, "FTAN"=>$recFtan->FTAN, "name"=>$img->title, "id"=>$img->id, "src"=>$img->src);
            }
          }
      }
  }


  function writeXml($doc, $TSK, $ESC){
    $dirname=$this->exportPath.$this->folderName;

    if (!file_exists($dirname))
     mkdir($dirname, 0777, true);

     if($TSK!='AUT' && $TSK!='BIB'){
       $tipoCont="S";
     }else{
       $tipoCont="A";
     }

     echo $doc->save($dirname."/".$tipoCont.$ESC.$TSK.".xml");

  }


  function generateImmFtan(){
    $dirname=$this->exportPath.$this->folderName;

    $docImm = new DOMDocument();
    $docImm->formatOutput = true;

    $root = $docImm->createElement('csm_immftan');
    $root = $docImm->appendChild($root);

    $subroot = $docImm->createElement('csm_def');
    $subroot = $root->appendChild($subroot);

    $cont=1;
    foreach($this->recImmFTAN as $recImm){
      $relaz = $docImm->createElement('relazione');
      $relaz = $subroot->appendChild($relaz);

      $prog = $docImm->createElement('prog');
      $prog->nodeValue=$cont++;
      $prog = $relaz->appendChild($prog);

      $file = $docImm->createElement('file');
      $file->nodeValue=$recImm["name"];
      $file = $relaz->appendChild($file);

      $idallegato = $docImm->createElement('identificativo_allegato');
      $idallegato = $relaz->appendChild($idallegato);

      $nome = $docImm->createElement('nome');
      $nome->nodeValue="FTAN";
      $nome = $idallegato->appendChild($nome);

      $valore = $docImm->createElement('valore');
      $valore->nodeValue=$recImm["FTAN"];
      $valore = $idallegato->appendChild($valore);

      $idbene = $docImm->createElement('identificativo_bene');
      $idbene = $relaz->appendChild($idbene);

      $nctr = $docImm->createElement('nctr');
      $nctr->nodeValue=$recImm["NCTR"];
      $nctr = $idbene->appendChild($nctr);

      $nctn = $docImm->createElement('nctn');
      $nctn->nodeValue=$recImm["NCTN"];
      $nctn = $idbene->appendChild($nctn);

      file_put_contents($dirname . '/' . $recImm["name"], file_get_contents($this->dam->streamUrl( $recImm['id'], 'original')));
      var_dump($this->dam->streamUrl( $recImm['id'], 'original'));
      var_dump($dirname);
      //die;
    }


    $docImm->save($dirname."/"."IMMFTAN.xml");
  }


  function generateGeoInfo(){
    $dirname=$this->exportPath.$this->folderName;

    $docInforma = new DOMDocument();
    $docInforma->formatOutput = true;

    $root = $docInforma->createElement('records');
    $root = $docInforma->appendChild($root);

    $docInforma->save($dirname."/"."geoInfo.xml");
  }


  function generateInforma($ESC){
    $dirname=$this->exportPath.$this->folderName;

    $docInforma = new DOMDocument();
    $docInforma->formatOutput = true;

    $root = $docInforma->createElement('csm_informa');
    $root = $docInforma->appendChild($root);

    $subroot = $docInforma->createElement('csm_def');
    $subroot = $root->appendChild($subroot);

    $nArchivio = $docInforma->createElement('nome_archivio');
    $nArchivio->nodeValue="Export";
    $nArchivio = $subroot->appendChild($nArchivio);

    $versione = $docInforma->createElement('versione');
    $versione->nodeValue="1.0";
    $versione = $subroot->appendChild($versione);

    $dataArchivio = $docInforma->createElement('data_archivio');
    $dataArchivio->nodeValue=date('d/m/Y');
    $dataArchivio = $subroot->appendChild($dataArchivio);

    $eSchedatore = $docInforma->createElement('ente_schedatore');
    $eSchedatore->nodeValue=$ESC;
    $eSchedatore = $subroot->appendChild($eSchedatore);

    $eCompetente = $docInforma->createElement('ente_competente');
    $eCompetente->nodeValue=$ESC;
    $eCompetente = $subroot->appendChild($eCompetente);

    $fFinanziaria = $docInforma->createElement('fonte_finanziaria');
    $fFinanziaria->nodeValue=$ESC;
    $fFinanziaria = $subroot->appendChild($fFinanziaria);

    $mfil = $docInforma->createElement('mfil');
    $mfil->nodeValue="0";
    $mfil = $subroot->appendChild($mfil);

    $sped = $docInforma->createElement('sped');
    $sped->nodeValue="0";
    $sped = $subroot->appendChild($sped);

    $nsup = $docInforma->createElement('nsup');
    $nsup->nodeValue="0";
    $nsup = $subroot->appendChild($nsup);

    $tsup = $docInforma->createElement('tsup');
    $tsup->nodeValue="0";
    $tsup = $subroot->appendChild($tsup);

    $docInforma->save($dirname."/"."INFORMA.xml");
  }


  function exportGroup($work_ids, $modulePath, $moduleName, $exportPath, $folderName, $autbib){

    $this->$moduleName=$moduleName;
    $this->exportPath=$exportPath;
    $this->folderName=$folderName;
    $this->autbib=$autbib;
    $this->dam = org_glizy_ObjectFactory::createObject("metafad.teca.DAM.services.ImportMedia");

    $moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');
    $schema = $moduleService->getElements($moduleName);
    $work = org_glizy_ObjectFactory::createModel('userModules.' . $moduleName . '.models.Model');

    if (!preg_match('/scheda([a-z]+?)([0-9]{3})/i', $moduleName, $matches))
        preg_match('/([a-z]+?)([0-9]{3})/i', $moduleName, $matches);

    $this->TSK = strtoupper($matches[1]);
    $this->version = $matches[2];

    $doc = new DOMDocument();
    $doc->formatOutput = true;

    $root = $doc->createElement('csm_root');
    $root = $doc->appendChild($root);

    //////// ----------------- INFO
    $rooti = $doc->createElement('csm_info');
    $rooti = $root->appendChild($rooti);

    $newNodeNorm = $doc->createElement('nome_normativa');
    $newNodeNorm->nodeValue = $this->TSK;
    $newNodeNorm = $rooti->appendChild($newNodeNorm);

    if($this->TSK!='AUT' && $this->TSK!='BIB'){
      $schTipo="scheda di catalogo";
    }else{
      $schTipo="authority files";
    }

    $newNodeTipo = $doc->createElement('tipo');
    $newNodeTipo->nodeValue = $schTipo; //per i file di controllo BIB e AUT deve essere "authority files"
    $newNodeTipo = $rooti->appendChild($newNodeTipo);

    $newNodeVer = $doc->createElement('ver_numero');
    $newNodeVer->nodeValue = $this->version[0].'.'.substr($this->version, 1);
    $newNodeVer = $rooti->appendChild($newNodeVer);

    $newNodeData = $doc->createElement('data_crea');
    $newNodeData->nodeValue = date('dmY');
    $newNodeData = $rooti->appendChild($newNodeData);

    // $newNodeEnte = $doc->createElement('ente_schedatore');// ESC
    // $newNodeEnte->nodeValue = "???";
    // $newNodeEnte = $rooti->appendChild($newNodeEnte);

    $newNodeConc = $doc->createElement('concessione');
    $newNodeConc = $rooti->appendChild($newNodeConc);

    $newNodeSped = $doc->createElement('spedizione');
    $newNodeSped = $rooti->appendChild($newNodeSped);

    $newNodeNote = $doc->createElement('note');
    $newNodeNote = $rooti->appendChild($newNodeNote);

    $newNodeNSchede = $doc->createElement('numero_schede');
    $newNodeNSchede->nodeValue = "".sprintf("%08d", count($work_ids));
    $newNodeNSchede = $rooti->appendChild($newNodeNSchede);
    //////// ----------------- INFO

    $roots = $doc->createElement('schede');
    $roots = $root->appendChild($roots);

    foreach ($work_ids as $id){
      $node = $doc->createElement('scheda');
      $node = $roots->appendChild($node);

      $work->load($id);
      $data=$work->getRawData();

      echo "<br>$id $data->ESC";
      //var_dump($data);

      $this->scanBibAut($data);

      $this->scanFtan($data);

      $this->appendInformations($schema, $data, $node, $doc);
    }

    if($this->TSK!='AUT' && $this->TSK!='BIB'){
      $this->ESC=$data->ESC;
    }

    $newNodeEnte = $doc->createElement('ente_schedatore');// ESC
    $newNodeEnte->nodeValue = $this->ESC;
    $newNodeEnte = $rooti->appendChild($newNodeEnte);

    $this->writeXml($doc, $this->TSK, $this->ESC);//MZ aggiungere ente schedatore

    if($this->TSK!='AUT' && $this->TSK!='BIB'){

      $applicationPath = org_glizy_Paths::get('APPLICATION');

      if($this->recAut && $this->autbib=="true"){
        $modulePath = $applicationPath . 'classes/userModules/' . 'AUT300' . '/';
        $this->exportGroup($this->recAut, $modulePath, 'AUT300', $this->exportPath, $this->folderName);
      }
      if($this->recBib && $this->autbib=="true"){
        $modulePath = $applicationPath . 'classes/userModules/' . 'BIB300' . '/';
        $this->exportGroup($this->recBib, $modulePath, 'BIB300', $this->exportPath, $this->folderName);
      }

      $this->generateImmFtan();
      $this->generateGeoInfo();
      $this->generateInforma($data->ESC);

      $this->zipDir($this->exportPath,$this->folderName);

    }

  }

  function zipDir($baseDir, $folderName){
    ini_set('memory_limit', '1024M');
    $currentDir = getcwd();
    chdir($baseDir);
    exec("zip -r $folderName.zip $folderName");
    $this->rrmdir($folderName);
    chdir($currentDir);
  }

  function rrmdir($path)
  {
      return is_file($path)?
      @unlink($path):
      array_map('rrmdir',glob($path.'/*'))==@rmdir($path);
  }

}
